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
    $cars = Vehicle::whereFlagActive("Y")->get(["police_number"])->pluck("police_number");
    $carStat = [];
    foreach ($cars as $car) {
      $dateToLook = [Carbon::now(), Carbon::now()->addMonth(-1), Carbon::now()->addMonth(-2)];
      foreach ($dateToLook as $date) {
        $startDate = $date->startOfMonth()->toDateString();
        $endDate = $date->endOfMonth()->toDateString();
        $counter = Counter::whereType("vehicles")
          ->whereField($car)
          ->whereBetween("date", [$startDate, $endDate]);
        $carStat[$car][] = $counter->sum("value");
      }
    }

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
