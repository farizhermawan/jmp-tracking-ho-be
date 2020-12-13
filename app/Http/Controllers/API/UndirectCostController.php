<?php

namespace App\Http\Controllers\API;

use App\Enums\DebitCredit;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\Enums\RefCode;
use App\FinancialRecord;
use App\Helpers\Report\UndirectCostReport;
use App\Http\Controllers\Controller;
use App\RemovedTransaction;
use App\User;
use App\UndirectCost;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

/**
 * UndirectCostController
 *
 * @property User $user
 */
class UndirectCostController extends Controller
{

  public function getTransaksi(Request $request)
  {
    $param = json_decode($request->getContent());
    $categories = ["Pribadi", "Kendaraan", "Rumah Tangga", "Lain-lain"];
    foreach ($categories as $category) {
      $records[$category] = UndirectCost::getTransactions($category, "Semua", $param->dateStart, $param->dateEnd);
    }
    return response()->json(['message' => 'success', 'data' => $records], HttpStatus::SUCCESS);
  }

  public function saveTransaction(Request $request)
  {
    $this->user = Auth::user();
    $param = json_decode($request->getContent());

    try {
      DB::transaction(function () use ($param) {
        $entity = Entity::HO;

        // create new transaction
        $undirectCost = new UndirectCost();
        $undirectCost->category = $param->category;
        $undirectCost->subcategory = $param->subcategory;
        $undirectCost->note = $param->note;
        $undirectCost->total_cost = $param->cost;
        $additional_data = [];
        if (isset($param->driver)) $additional_data['driver'] = $param->driver->name;
        if (isset($param->police_number)) $additional_data['police_number'] = $param->police_number->police_number;
        $undirectCost->additional_data = $additional_data;
        $undirectCost->created_at = Carbon::now();
        $undirectCost->created_by = $this->user->name;
        $undirectCost->save();

        // Post finance record
        $keys = [$undirectCost->created_at->toDateString(), $undirectCost->subcategory];
        if (isset($additional_data['police_number'])) $keys[] = $additional_data['police_number'];
        if (isset($additional_data['driver'])) $keys[] = $additional_data['driver'];
        $message = "Biaya " . strtolower($undirectCost->category) . " tanggal " . implode(" / ", $keys);
        $postId = FinancialRecord::postFinancialRecord(RefCode::UNDIRECT, $undirectCost->id, $message, DebitCredit::CREDIT, $undirectCost->total_cost, $entity);

        // check if entity ballance enough
        if (FinancialRecord::getBallance($entity) < 0) {
          throw new Exception("Saldo akhir tidak boleh dibawah nol");
        }

        // link finance record with trx
        $undirectCost->post_id = [$postId];
        $undirectCost->save();
      });
    } catch (Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => "success"], HttpStatus::SUCCESS);
  }

  public function remove(Request $request)
  {
    $this->user = Auth::user();
    $param = json_decode($request->getContent());
    $undirectCost = UndirectCost::whereId($param->id)->first();
    if (!$undirectCost) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $undirectCost) {
        $entity = Entity::HO;

        // Records Remove Transaction History
        $remove = new RemovedTransaction();
        $remove->source = RefCode::UNDIRECT;
        $remove->ref = $undirectCost->id;
        $remove->additional_data = $undirectCost->toJson();
        $remove->created_by = $this->user->name;
        $remove->save();

        // Post finance record
        $additional_data = $undirectCost->additional_data;
        $keys = [$undirectCost->created_at->toDateString(), $undirectCost->subcategory];
        if (isset($additional_data['police_number'])) $keys[] = $additional_data['police_number'];
        if (isset($additional_data['driver'])) $keys[] = $additional_data['driver'];
        $message = "Pembatalan biaya " . strtolower($undirectCost->category) . " tanggal " . implode(" / ", $keys);
        $postId = FinancialRecord::postFinancialRecord(RefCode::UNDIRECT, $undirectCost->id, $message, DebitCredit::DEBIT, $undirectCost->total_cost, $entity);

        // link finance record with trx
        $remove->post_id = [$postId];
        $remove->save();

        // Remove the item
        $undirectCost->delete();
      });
    } catch (Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function exportUndirectCost(Request $request)
  {
    $param = json_decode($request->getContent());
    $report = new UndirectCostReport();
    return $report->generate($param);
  }
}
