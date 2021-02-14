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
use PhpOffice\PhpSpreadsheet\Style\Border;
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
        Counter::increase(CounterType::DRIVER, $param->driver->name);
        Counter::increase(CounterType::VEHICLES, $param->police_number->police_number);
        Counter::increase(CounterType::ROUTE, $param->route->name);
        Counter::increase(CounterType::CUSTOMER, $param->customer->name);
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success', 'data' => $jot], HttpStatus::SUCCESS);
  }

  public function reviseJot(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $oldJot = Transaction::whereId($param->id)->first();
    if (!$oldJot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    // Create new route if needed
    if ($param->route->id == null) $this->createRoute($param->route);

    $jot = null;
    try {
      $result = DB::transaction(function () use ($oldJot, $jot, $param) {
        // Remove old JOT
        $this->remove($oldJot);

        // Create new JOT
        $jot = $this->createJot($param);
        $jot->created_at = $oldJot->created_at;

        $entity = Entity::HO;

        // Post finance record
        $postId = FinancialRecord::postFinancialRecord(RefCode::DIRECT, $jot->id, "[Revisi] Uang jalan transaksi tanggal {$jot->created_at->toDateString()} / {$jot->route} / {$jot->police_number} / {$jot->driver_name}", DebitCredit::CREDIT, $jot->total_cost, $entity);

        // check if entity ballance enough
        if (FinancialRecord::getBallance($entity) < 0) {
          throw new Exception("Saldo akhir tidak boleh dibawah nol");
        }

        // Update JOT to link finance record
        $jot->post_id = [$postId];
        $jot->save();

        // Increment counter
        Counter::increase(CounterType::DRIVER, $param->driver->name);
        Counter::increase(CounterType::VEHICLES, $param->police_number->police_number);
        Counter::increase(CounterType::ROUTE, $param->route->name);
        Counter::increase(CounterType::CUSTOMER, $param->customer->name);

        return response()->json(['message' => 'success', 'data' => $jot], HttpStatus::SUCCESS);
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    }
    return $result;
  }

  public function savePlan(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    // Create new route if needed
    if ($param->route->id == null) $this->createRoute($param->route);

    $jot = null;
    try {
      DB::transaction(function () use ($jot, $param) {
        list($date, $monthName, $year) = explode(" ", $param->date);
        if ($monthName == "Januari") $month = 1;
        else if ($monthName == "Februari") $month = 2;
        else if ($monthName == "Maret") $month = 3;
        else if ($monthName == "April") $month = 4;
        else if ($monthName == "Mei") $month = 5;
        else if ($monthName == "Juni") $month = 6;
        else if ($monthName == "Juli") $month = 7;
        else if ($monthName == "Agustus") $month = 8;
        else if ($monthName == "September") $month = 9;
        else if ($monthName == "Oktober") $month = 10;
        else if ($monthName == "November") $month = 11;
        else if ($monthName == "Desember") $month = 12;
        $date = intval($date);
        $year = intval($year);
        // Create new JOT
        $jot = $this->createJot($param);
        $jot->created_at = Carbon::createFromDate($year, $month, $date);
        $jot->status = Common::PLAN;
        $jot->save();
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
    if ($param->field == "container_size") {
      $jot->container_size = strtoupper($param->value);
    } else if ($param->field == "kenek") {
      if ($jot->kenek_name == null) {
        $route = Route::whereName($jot->route)->first();
        $jot->commission2 = $route->additional_data['commission2'];
      } else if ($param->value == null) {
        $jot->commission2 = 0;
      }
      $jot->kenek_name = $param->value;
    } else if ($param->field == "confirm") {
      $this->user = \Auth::user();
      $confirmed_meta = [
        'confirmed_by' => $this->user->name,
        'confirmed_at' => Carbon::now()->toDateTimeString()
      ];
      $jot->additional_data = $confirmed_meta;
      $jot->status = Common::CONFIRMED;
    } else if ($param->field == "close") {
      $this->user = \Auth::user();
      $closed_meta = [
        'closed_by' => $this->user->name,
        'closed_at' => Carbon::now()->toDateTimeString()
      ];
      $jot->additional_data = array_merge((array) $jot->additional_data, $closed_meta);
      $jot->status = Common::CLOSED;
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

  public function removeJot(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());
    $jot = Transaction::whereId($param->id)->first();
    if (!$jot) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $jot) {
        $this->remove($jot);
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage(), 'e' => $e->getTrace(), 'f' => $e->getFile(), 'l' => $e->getLine()], HttpStatus::ERROR);
    }
    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function remove(Transaction $jot)
  {
    $this->user = \Auth::user();
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

    // Decrement counter
    Counter::decrease(CounterType::DRIVER, $jot->driver_name, $jot->created_at);
    Counter::decrease(CounterType::VEHICLES, $jot->police_number, $jot->created_at);
    Counter::decrease(CounterType::ROUTE, $jot->route, $jot->created_at);
    Counter::decrease(CounterType::CUSTOMER, $jot->customer_name, $jot->created_at);

    // Remove the item
    $jot->delete();
  }

  public function getTransaksi(Request $request)
  {
    $param = json_decode($request->getContent());
    $records = Transaction::getTransactions($param->dateStart, $param->dateEnd, $param->status);
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

      $records = Transaction::getTransactions($param->dateStart, $param->dateEnd, $param->status);

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
        $sheet->setCellValue("D" . ($startRow + $no), $record->kenek_name);
        $sheet->setCellValue("E" . ($startRow + $no), $record->police_number);
        $sheet->setCellValue("F" . ($startRow + $no), $record->container_size);
        $sheet->setCellValue("G" . ($startRow + $no), $record->customer_name);
        $sheet->setCellValue("H" . ($startRow + $no), $record->route);
        $sheet->setCellValue("I" . ($startRow + $no), $record->commission);
        $sheet->setCellValue("J" . ($startRow + $no), $record->commission2);
        $otherCostName = [];
        $otherCostColumn = ['W', 'V', 'U', 'T', 'S', 'R', 'Q', 'P', 'O', 'N'];
        foreach ($record->cost_entries as $cost_entry) {
          if ($cost_entry['item'] == Common::UANG_JALAN) $sheet->setCellValue("K" . ($startRow + $no), $cost_entry['value']);
          else if ($cost_entry['item'] == Common::BIAYA_SOLAR) $sheet->setCellValue("L" . ($startRow + $no), $cost_entry['value']);
          else {
            $otherCostName[] = $cost_entry['item'];
            $sheet->setCellValue(array_pop($otherCostColumn) . ($startRow + $no), $cost_entry['value']);
          }
        }
        if ($record->solar_cost > 0) {
          $sheet->setCellValue("L" . ($startRow + $no), $record->solar_cost);
        }
        $sheet->setCellValue("M" . ($startRow + $no), implode(", ", $otherCostName));

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

  public function exportDetail(Request $request)
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");
    $this->user = \Auth::user();

    $now = Carbon::now()->addMonth(1);
    $hash = md5($now->timestamp);
    $param = json_decode($request->getContent());
    $template = storage_path("app/report-template/transaksi-detail.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheetSupir = $spreadsheet->getSheetByName("komisi supir");
      $sheetKenek = $spreadsheet->getSheetByName("komisi kenek");
      $sheetSummary = $spreadsheet->getSheetByName("summary");

      $records = Transaction::getTransactions($param->dateStart, $param->dateEnd, $param->status);

      $startRow = 2;
      $minRow = 5;
      $totalRow = $records->count();
      if ($totalRow < $minRow) $totalRow = $minRow;
      if ($totalRow > $minRow) {
        $diffCount = $totalRow - $minRow;
        $sheetSupir->insertNewRowBefore(11, $diffCount);
        $sheetKenek->insertNewRowBefore(11, $diffCount);
      }

      $records = $records->all();
      usort($records, function($a, $b) {
        return $a->driver_name <=> $b->driver_name;
      });

      $no = 0;
      $lastDriver = null;
      $totalRecord = null;
      foreach ($records as $record) {
        if ($lastDriver == null) {
          $lastDriver = $record->driver_name;
          $totalRecord = 0;
        }
        $sheetSupir->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
        $sheetSupir->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
        $sheetSupir->setCellValue("C" . ($startRow + $no), $record->driver_name);
        $sheetSupir->setCellValue("D" . ($startRow + $no), $record->police_number);
        $sheetSupir->setCellValue("E" . ($startRow + $no), $record->customer_name);
        $sheetSupir->setCellValue("F" . ($startRow + $no), $record->route);
        $sheetSupir->setCellValue("G" . ($startRow + $no), $record->commission);
        if ($record->solar_cost > 0) {
          $sheetSupir->setCellValue("H" . ($startRow + $no), $record->solar_cost);
        } else {
          foreach ($record->cost_entries as $cost_entry) {
            if ($cost_entry['item'] == Common::BIAYA_SOLAR) {
              $sheetSupir->setCellValue("H" . ($startRow + $no), $cost_entry['value']);
              break;
            }
          }
        }

        $totalRecord++;
        $no++;
        if ($lastDriver != $record->driver_name || $no == $totalRow) {
          $lastDriver = null;
          $end = $no;
          $start = $end - $totalRecord + 1;
          if ($start < $startRow) $start = $startRow;
          if ($end == $totalRow) $end++;
          $sheetSupir->mergeCellsByColumnAndRow(10, $start, 10, $end);
          $sheetSupir->setCellValue("J" . ($start), sprintf("=SUM(G%s:I%s)", $start, $end));
          $sheetSupir->getStyle("A{$start}:J{$end}")->applyFromArray([
            'borders' => [
              'outline' => [
                'borderStyle' => Border::BORDER_THIN
              ]
            ]
          ]);
        }
      }
      $sheetSummary->setCellValue("B3", "=SUM('komisi supir'!G{$startRow}:G{$end})");
      $sheetSummary->setCellValue("B4", "=SUM('komisi supir'!H{$startRow}:H{$end})");
      $sheetSummary->setCellValue("B6", "=SUM('komisi supir'!I{$startRow}:I{$end})");

      usort($records, function($a, $b) {
        return $a->kenek_name <=> $b->kenek_name;
      });

      $no = 0;
      $lastKenek = null;
      $totalRecord = null;
      $totalSkipped = 0;
      foreach ($records as $record) {
        if ($record->kenek_name == "") {
          $totalSkipped++;
          continue;
        }
        if ($lastKenek == null) {
          $lastKenek = $record->kenek_name;
          $totalRecord = 0;
        }
        $sheetKenek->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
        $sheetKenek->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
        $sheetKenek->setCellValue("C" . ($startRow + $no), $record->kenek_name);
        $sheetKenek->setCellValue("D" . ($startRow + $no), $record->driver_name);
        $sheetKenek->setCellValue("E" . ($startRow + $no), $record->police_number);
        $sheetKenek->setCellValue("F" . ($startRow + $no), $record->customer_name);
        $sheetKenek->setCellValue("G" . ($startRow + $no), $record->route);
        $sheetKenek->setCellValue("H" . ($startRow + $no), $record->commission2);
        $totalRecord++;
        $no++;
        if ($lastKenek != $record->kenek_name || $no + $totalSkipped == $totalRow) {
          $lastKenek = null;
          $end = $no;
          $start = $end - $totalRecord + 1;
          if ($start < $startRow) $start = $startRow;
          if ($end == $totalRow - $totalSkipped) $end++;
          $sheetKenek->mergeCellsByColumnAndRow(10, $start, 10, $end);
          $sheetKenek->setCellValue("J" . ($start), sprintf("=SUM(H%s:I%s)", $start, $end));
          $sheetKenek->getStyle("A{$start}:J{$end}")->applyFromArray([
            'borders' => [
              'outline' => [
                'borderStyle' => Border::BORDER_THIN
              ]
            ]
          ]);
        }
      }
      $sheetSummary->setCellValue("B5", "=SUM('komisi kenek'!H{$startRow}:H{$end})");
      $sheetSummary->setCellValue("B7", "=SUM('komisi kenek'!I{$startRow}:I{$end})");

      $spreadsheet->setActiveSheetIndex(0);
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
    $jot->commission2 = 0;
    $jot->solar_cost = 0;
    $jot->cost_entries = $costEntries = [];
    $jot->container_size = $param->container_size;
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
    if (isset($param->kenek->name)) {
      $jot->kenek_name = $param->kenek->name;
      if (isset($param->route->additional_data->commission2)) {
        $jot->commission2 = $param->route->additional_data->commission2;
      }
    }

    // solar cost
    if ($param->solar_cost > 0) {
      $jot->solar_cost = $param->solar_cost;
    }

    if (isset($param->container_no)) $jot->container_no = $param->container_no;
    if (isset($param->sub_customer->name)) $jot->subcustomer_name = $param->sub_customer->name;
    if (isset($param->depo_mt->name)) $jot->depo_mt = $param->depo_mt->name;

    $jot->save();
    return $jot;
  }
}
