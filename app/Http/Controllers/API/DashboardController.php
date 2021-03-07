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
    $dashboard = [
      'today_transaction' => Transaction::whereDate('created_at', '>=', $now->toDateString())->whereDate('created_at', '<=', $now->toDateString())->count(),
      'open_transaction' => Transaction::whereStatus(Common::OPEN)->count() + Transaction::whereStatus(Common::CONFIRMED)->count(),
      'ballance' => [
        number_format(FinancialRecord::getBallance(Entity::HO), 0, ',', '.'),
        number_format(FinancialRecord::getBallance(Entity::BANK), 0, ',', '.'),
      ]
    ];

    return response()->json(['data' => $dashboard], HttpStatus::SUCCESS);
  }

  public function getRitasi()
  {
    $selectedPeriode = Carbon::now();
    if (isset($_GET['date'])) {
      list($monthName, $year) = explode(" ", $_GET['date']);
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
      $year = intval($year);
      $selectedPeriode = Carbon::createFromDate($year, $month);
    }
    $cars = Vehicle::whereFlagActive("Y")->get(["police_number"])->pluck("police_number");
    $carStat = [];
    $dateToLook = [
      $selectedPeriode,
      $selectedPeriode->copy()->addMonth(-1),
      $selectedPeriode->copy()->addMonth(-2),
      $selectedPeriode->copy()->addMonth(-3)
    ];
    foreach ($cars as $car) {
      foreach ($dateToLook as $date) {
        $startDate = $date->startOfMonth()->toDateString();
        $endDate = $date->endOfMonth()->toDateString();
        $counter = Counter::whereType("vehicles")
          ->whereField($car)
          ->whereBetween("date", [$startDate, $endDate]);
        $carStat[$car][] = $counter->sum("value");
      }
    }

    return response()->json(['data' => $carStat], HttpStatus::SUCCESS);
  }
}
