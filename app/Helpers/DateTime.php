<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateTime
{
    static function extractDate(Carbon $date) {
        return [
            'date' => $date->toDateString(),
            'time' => $date->toTimeString(),
            'timestamp' => $date->timestamp
        ];
    }
}
