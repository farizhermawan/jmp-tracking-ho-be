<?php

namespace App\Http\Controllers\API;

use App\AdjustTransaction;
use App\Counter;
use App\Enums\Common;
use App\Enums\CounterType;
use App\Enums\DebitCredit;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\Enums\RefCode;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\RemovedTransaction;
use App\Route;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * TransactionController
 *
 * @property \App\User $user
 */
class TransactionController extends Controller
{
  private $user;

  public function viewJot(Request $request)
  {
    $param = json_decode($request->getContent());
    $jot = Transaction::whereId($param->id)->first();
    if (!$jot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);
    return response()->json(['message' => 'success', 'data' => $jot], HttpStatus::SUCCESS);
  }

  public function saveJot(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    // Create new route if needed
    if ($param->route->id == null) $this->createRoute($param->route);

    $jot = null;
    try {
      DB::transaction(function () use ($jot, $param) {
        // Create new JOT
        $jot = $this->createJot($param);
        $entity = Entity::HO;

        // Post finance record
        $postId = FinancialRecord::postFinancialRecord(RefCode::DIRECT, $jot->id, "Uang jalan transaksi tanggal {$jot->created_at->toDateString()} / {$jot->route} / {$jot->police_number} / {$jot->driver_name}", DebitCredit::CREDIT, $jot->total_cost, $entity);

        // check if entity ballance enough
        if (FinancialRecord::getBallance($entity) < 0) {
          throw new Exception("Saldo akhir tidak boleh dibawah nol");
        }

        // Update JOT to link finance record
        $jot->post_id = [$postId];
        $jot->save();

        // Increment counter
        Counter::add(CounterType::DRIVER, $param->driver->name);
        Counter::add(CounterType::VEHICLES, $param->police_number->police_number);
        Counter::add(CounterType::ROUTE, $param->route->name);
        Counter::add(CounterType::CUSTOMER, $param->customer->name);
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success', 'data' => $jot], HttpStatus::SUCCESS);
  }

  public function updateJot(Request $request)
  {
    $param = json_decode($request->getContent());
    $jot = Transaction::whereId($param->key)->first();
    if (!$jot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);
    $param->value = strtoupper($param->value);
    if ($param->field == "container_size") {
      $jot->container_size = $param->value;
    }
    $jot->save();
    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function adjustJot(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());
    $jot = Transaction::whereId($param->id)->first();
    if (!$jot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $jot) {
        // Get record entity
        $entity = Entity::HO;

        $newCost = 0;
        foreach ($param->addons as $addon) {
          $newCost += $addon->value;
        }

        // Records Adjustment History
        $adjust = new AdjustTransaction();
        $adjust->old_value = $jot->cost_entries;
        $adjust->new_value = $param->addons;
        $adjust->discrepancy = $newCost - $jot->total_cost;
        $adjust->created_by = $this->user->name;
        $adjust->ref = $jot->id;
        $adjust->save();

        // Post finance record
        $postId = FinancialRecord::postFinancialRecord(RefCode::ADJUSTMENT, $adjust->id, "Adjustment transaksi tanggal {$jot->created_at->toDateString()} / {$jot->route} / {$jot->police_number} / {$jot->driver_name}", $adjust->discrepancy > 0 ? DebitCredit::CREDIT : DebitCredit::DEBIT, abs($adjust->discrepancy), $entity);

        // Update JOT records
        $jotPostId = $jot->post_id;
        $jotPostId[] = $postId;
        $jot->total_cost = $newCost;
        $jot->cost_entries = $param->addons;
        $jot->post_id = $jotPostId;
        $jot->save();

        // check if entity ballance enough
        if (FinancialRecord::getBallance($entity) < 0) {
          throw new Exception("Saldo akhir tidak boleh dibawah nol");
        }

        // Update Adjustment to link finance record
        $adjust->post_id = [$postId];
        $adjust->save();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function remove(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());
    $jot = Transaction::whereId($param->id)->first();
    if (!$jot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $jot) {
        // Get record entity
        $entity = Entity::HO;

        // Records Remove Transaction History
        $remove = new RemovedTransaction();
        $remove->source = RefCode::DIRECT;
        $remove->ref = $jot->id;
        $remove->additional_data = $jot->toJson();
        $remove->created_by = $this->user->name;
        $remove->save();

        // Post finance record
        $postId = FinancialRecord::postFinancialRecord(RefCode::DELETE, $remove->id, "Pembatalan transaksi tanggal {$jot->created_at->toDateString()} / {$jot->route} / {$jot->police_number} / {$jot->driver_name}", DebitCredit::DEBIT, $jot->total_cost, $entity);

        // Update Remove Transaction to link finance record
        $remove->post_id = [$postId];
        $remove->save();

        // Remove the item
        $jot->delete();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function getTransaksi(Request $request)
  {
    $param = json_decode($request->getContent());
    $records = Transaction::getTransactions($param->dateStart, $param->dateEnd);
    return response()->json(['message' => 'success', 'data' => $records], HttpStatus::SUCCESS);
  }

//  public function search(Request $request)
//  {
//    $param = json_decode($request->getContent());
//    $data = Transaction::select(['id', 'container', 'activity_code', 'total_cost'])->where('container', 'like', '%' . $param->q . '%')->get();
//    return response()->json(['message' => 'success', 'data' => $data], HttpStatus::SUCCESS);
//  }

  public function export(Request $request)
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");
    $this->user = \Auth::user();

    $now = Carbon::now()->addMonth(1);
    $hash = md5($now->timestamp);
    $param = json_decode($request->getContent());
    $template = storage_path("app/report-template/transaksi.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $records = Transaction::getTransactions($param->dateStart, $param->dateEnd);

      $startRow = 2;
      $minRow = 5;
      $totalRow = $records->count();
      if ($totalRow < $minRow) $totalRow = $minRow;
      if ($totalRow > $minRow) {
        $diffCount = $totalRow - $minRow;
        $sheet->insertNewRowBefore(11, $diffCount);
      }
      $no = 0;
      foreach ($records as $record) {
        $sheet->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
        $sheet->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
        $sheet->setCellValue("C" . ($startRow + $no), $record->driver_name);
        $sheet->setCellValue("D" . ($startRow + $no), $record->police_number);
        $sheet->setCellValue("E" . ($startRow + $no), $record->customer_name);
        $sheet->setCellValue("F" . ($startRow + $no), $record->route);
        $sheet->setCellValue("G" . ($startRow + $no), $record->commission);
        $sheet->setCellValue("H" . ($startRow + $no), $record->total_cost);
        $no++;
      }

      $writer = new Xlsx($spreadsheet);
      $writer->save(storage_path("app/public/{$hash}.xlsx"));

      return response()->json(['message' => "success", "hash" => $hash], HttpStatus::SUCCESS);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    }
  }

  private function createRoute($route)
  {
    $newRoute = new Route();
    $newRoute->name = $route->name;
    $newRoute->created_by = $this->user->name;
    $newRoute->save();
  }

  private function createJot($param)
  {
    $jot = new Transaction();
    $jot->customer_name = $param->customer->name;
    $jot->driver_name = $param->driver->name;
    $jot->police_number = $param->police_number->police_number;
    $jot->route = $param->route->name;
    $jot->total_cost = 0;
    $jot->commission = 0;
    $jot->cost_entries = $costEntries = [];
    $jot->container_size = $param->container_size->value;
    $jot->created_at = Carbon::now();
    $jot->created_by = $this->user->name;

    // calculate cost
    $defaultAddons = [];
    $availableAddons = [];
    $costEntries[] = ["item" => Common::UANG_JALAN, "value" => $param->cost];
    $jot->total_cost += $param->cost;
    foreach ($param->addons as $addon) {
      $availableAddons[$addon->item] = true;
      $costEntries[] = ["item" => $addon->item, "value" => $addon->value];
      $jot->total_cost += $addon->value;
    }
    foreach ($defaultAddons as $addon) if (!isset($availableAddons[$addon])) $costEntries[] = ["item" => $addon, "value" => 0];
    $jot->cost_entries = $costEntries;

    // commission
    if (isset($param->route->additional_data->commission)) {
      $jot->commission = $param->route->additional_data->commission;
    }

    $jot->save();
    return $jot;
  }
}
