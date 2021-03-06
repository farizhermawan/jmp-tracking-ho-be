<?php

namespace App\Http\Controllers\API;

use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{

  public function getMonitor(Request $request)
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");

    $param = json_decode($request->getContent());
    $date = !empty($param->date) ? Carbon::parse($param->date)->setTimezone('Asia/Jakarta')->toDateString() : Carbon::now()->toDateString();

    $trxs = Transaction::getTransactionsForMonitor($date);
    $result = [];
    $ritasi = [];
    foreach ($trxs as $trx) {
      if (isset($ritasi[$trx->police_number])) $ritasi[$trx->police_number]++;
      else $ritasi[$trx->police_number] = 1;

      $cust_name = trim(str_replace("PT", "", $trx->customer_name));
      if (strlen($cust_name) > 15) $cust_name = substr($cust_name, 0, 15) . "...";
      $result[] = [
        'id' => $trx->id,
        'police_number' => $trx->police_number,
        'itruck' => $trx->itruck,
        'driver_name' => $trx->driver_name,
        'customer_name' => $cust_name,
        'transaction_time' => $trx->created_at->format("H:i"),
        'ritasi' => $ritasi[$trx->police_number],
        'status' => $trx->status
      ];
    }
    return response()->json(['date' => $date, 'data' => $result], HttpStatus::SUCCESS);
  }
}
