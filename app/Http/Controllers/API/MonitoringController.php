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

    $trxs = Transaction::getTransactions($date, $date);
    $result = [];
    $ritasi = [];
    foreach ($trxs as $trx) {
      if (isset($ritasi[$trx->police_number])) $ritasi[$trx->police_number]++;
      else $ritasi[$trx->police_number] = 1;

      $result[] = [
        'police_number' => $trx->police_number,
        'driver_name' => $trx->driver_name,
        'customer_name' => $trx->customer_name,
        'transaction_time' => $trx->created_at->format("h:m"),
        'ritasi' => $ritasi[$trx->police_number],
        'status' => $trx->status
      ];
    }
    return response()->json(['date' => $date, 'data' => $result], HttpStatus::SUCCESS);
  }
}
