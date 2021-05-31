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
use App\Helpers\Report\TransactionDetailReport;
use App\Helpers\Report\TransactionReport;
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
    if ($param->field == "itruck") {
      if (Transaction::whereItruck($param->value)->exists()) return response()->json(['message' => 'No I-Truck sudah digunakan'], HttpStatus::SUCCESS);
      $jot->itruck = $param->value;
    }
    else if ($param->field == "container_no") {
      $jot->container_no = $param->value;
    }
    else if ($param->field == "container_size") {
      $jot->container_size = strtoupper($param->value);
    }
    else if ($param->field == "subcustomer_name") {
      $jot->subcustomer_name = $param->value;
    }
    else if ($param->field == "depo_mt") {
      $jot->depo_mt = $param->value;
    }
    else if ($param->field == "kenek") {
      if ($jot->kenek_name == null) {
        $route = Route::whereName($jot->route)->first();
        $jot->commission2 = $route->additional_data['commission2'];
      } else if ($param->value == null) {
        $jot->commission2 = 0;
      }
      $jot->kenek_name = $param->value;
    }
    else if ($param->field == "confirm") {
      $this->user = \Auth::user();
      $confirmed_meta = [
        'confirmed_by' => $this->user->name,
        'confirmed_at' => Carbon::now()->toDateTimeString()
      ];
      $jot->additional_data = $confirmed_meta;
      $jot->status = Common::CONFIRMED;
    }
    else if ($param->field == "close") {
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
    $param = json_decode($request->getContent());
    $report = new TransactionReport();
    return $report->generate($param);
  }

  public function exportDetail(Request $request)
  {
    $param = json_decode($request->getContent());
    $report = new TransactionDetailReport();
    return $report->generate($param);
  }

  public function validation(Request $request) {
    $result = ["passed" => false, "error" => []];
    $param = json_decode($request->getContent());
    if (!empty($param->itruck)) {
      if (!Transaction::whereItruck($param->itruck)->exists()) {
        $result["errors"][] = ["key" => "itruck", "error" => "No I-Truck {$param->itruck} telah digunakan"];
      }
    }
    $result["passed"] = true;
    return response()->json($result, HttpStatus::SUCCESS);
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

    if (isset($param->itruck)) {
      if (Transaction::whereItruck($param->itruck)->exists()) throw new Exception("No I-Truck {$param->itruck} telah digunakan");
      $jot->itruck = $param->itruck;
    }
    if (isset($param->container_no)) $jot->container_no = $param->container_no;
    if (isset($param->sub_customer->name)) $jot->subcustomer_name = $param->sub_customer->name;
    if (isset($param->depo_mt->name)) $jot->depo_mt = $param->depo_mt->name;

    $jot->save();
    return $jot;
  }
}
