<?php

namespace App\Http\Controllers\API;

use App\Counter;
use App\Enums\Common;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\Vehicle;
use Carbon\Carbon;

class DashboardController extends Controller
{
  public function getInfo()
  {
    $now = Carbon::now();
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();
    $cars = Vehicle::whereFlagActive("Y")->get(["police_number"])->pluck("police_number");
    $carStat = [];
    foreach ($cars as $car) {
      $counter = Counter::whereType("vehicles")
        ->whereField($car)
        ->whereBetween("date", [$startDate->toDateString(), $endDate->toDateString()]);
      $carStat[$car] = $counter->sum("value");
    }
    arsort($carStat);

    $dashboard = [
      'today_transaction' => Transaction::whereDate('created_at', '>=', $now->toDateString())->whereDate('created_at', '<=', $now->toDateString())->count(),
      'open_transaction' => Transaction::whereStatus(Common::OPEN)->count(),
      'ballance' => [
        number_format(FinancialRecord::getBallance(Entity::HO), 0, ',', '.'),
      ],
      'car_stat' => $carStat
    ];

    return response()->json(['data' => $dashboard], HttpStatus::SUCCESS);
  }
}
