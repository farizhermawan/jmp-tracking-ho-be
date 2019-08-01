<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CounterType extends Enum
{
    const DRIVER = "driver";
    const VEHICLES = "vehicles";
    const ROUTE = "route";
    const CUSTOMER = "customer";
}
