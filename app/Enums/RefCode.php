<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RefCode extends Enum
{
    const DIRECT = "L";
    const UNDIRECT = "U";
    const BALLANCE = "S";
    const ADJUSTMENT = "A";
    const DELETE = "D";
}
