<?php

namespace App\Http\Controllers\API;

use App\Driver;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getInfo()
    {
        $now = Carbon::now();

        $dashboard = [
            'today_transaction' => Transaction::whereDate('created_at', '>=', $now->toDateString())->whereDate('created_at', '<=', $now->toDateString())->count(),
            'ballance' => [
                number_format(FinancialRecord::getBallance(Entity::HO), 0, ',', '.'),
            ]
        ];

        return response()->json(['data' => $dashboard], HttpStatus::SUCCESS);
    }
}
