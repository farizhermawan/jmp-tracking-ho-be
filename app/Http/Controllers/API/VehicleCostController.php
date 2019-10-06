<?php

namespace App\Http\Controllers\API;

use App\Enums\DebitCredit;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\Enums\RefCode;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\RemovedTransaction;
use App\VehicleCost;
use DB;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * UndirectCostController
 *
 * @property \App\User $user
 */
class VehicleCostController extends Controller
{

  public function getTransaksi(Request $request)
  {
    $param = json_decode($request->getContent());
    $records = VehicleCost::getTransactions($param->category, $param->dateStart, $param->dateEnd);
    return response()->json(['message' => 'success', 'data' => $records], HttpStatus::SUCCESS);
  }

  public function saveTransaction(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    try {
      DB::transaction(function () use ($param) {
        $entity = Entity::HO;

        // create new transaction
        $vehicleCost = new VehicleCost();
        $vehicleCost->category = $param->category;
        $vehicleCost->note = $param->note;
        $vehicleCost->total_cost = $param->cost;
        $additional_data = [];
        if (isset($param->driver)) $additional_data['driver'] = $param->driver->name;
        if (isset($param->police_number)) $additional_data['police_number'] = $param->police_number->police_number;
        $vehicleCost->additional_data = $additional_data;
        $vehicleCost->created_by = $this->user->name;
        $vehicleCost->save();


        // Post finance record
        $postId = FinancialRecord::postFinancialRecord(RefCode::UNDIRECT, $vehicleCost->id, "Biaya atas kendaraan {$additional_data['police_number']} / {$additional_data['driver']} / {$param->category}", DebitCredit::CREDIT, $vehicleCost->total_cost, $entity);

        // check if entity ballance enough
        if (FinancialRecord::getBallance($entity) < 0) {
          throw new Exception("Saldo akhir tidak boleh dibawah nol");
        }

        // Update JOT to link finance record
        $vehicleCost->post_id = [$postId];
        $vehicleCost->save();

      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);                // Create new JOT$undirect->created_by = $this->user->name;
    }

    return response()->json(['message' => "success"], HttpStatus::SUCCESS);
  }

  public function remove(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());
    $vehicleCost = VehicleCost::whereId($param->id)->first();
    if (!$vehicleCost) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $vehicleCost) {
        $entity = Entity::HO;

        // Records Remove Transaction History
        $remove = new RemovedTransaction();
        $remove->source = RefCode::UNDIRECT;
        $remove->ref = $vehicleCost->id;
        $remove->additional_data = $vehicleCost->toJson();
        $remove->created_by = $this->user->name;
        $remove->save();

        // Post finance record
        $additional_data = $vehicleCost->additional_data;
        $postId = FinancialRecord::postFinancialRecord(RefCode::DELETE, $remove->id, "Pembatalan transaksi biaya atas kendaraan {$additional_data['police_number']} / {$additional_data['driver']} / {$vehicleCost->category}", DebitCredit::DEBIT, $vehicleCost->total_cost, $entity);

        // Update Remove Transaction to link finance record
        $remove->post_id = [$postId];
        $remove->save();

        // Remove the item
        $vehicleCost->delete();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function export(Request $request)
  {
    $hash = md5(time());
    $param = json_decode($request->getContent());
    $template = storage_path("app/report-template/undirect.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $displayDateFormat = "Y-m-d";
      $entity = Entity::get($param->entity->id);
      $items = UndirectTransaction::getTransactions($entity, $param->category, $param->dateStart, $param->dateEnd);

      $row = 1;
      foreach ($items as $item) {
        $row++;
        $sheet->setCellValue("A" . $row, $item->created_at->format($displayDateFormat));
        $sheet->setCellValue("B" . $row, $item->category);
        $sheet->setCellValue("C" . $row, $item->note);
        $sheet->setCellValue("D" . $row, isset($item->additional_data['employee']) ? $item->additional_data['employee'] : "");
        $sheet->setCellValue("E" . $row, isset($item->additional_data['police_number']) ? $item->additional_data['police_number'] : "");
        $sheet->setCellValue("F" . $row, $item->total_cost);
      }
      $writer = new Xlsx($spreadsheet);
      $writer->save(storage_path("app/public/{$hash}.xlsx"));

      return response()->json(['message' => "success", "hash" => $hash], HttpStatus::SUCCESS);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
  }
}
