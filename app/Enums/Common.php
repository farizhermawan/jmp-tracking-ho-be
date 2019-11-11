<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Common extends Enum
{
    const UANG_JALAN  = "Uang Jalan";
    const BIAYA_SOLAR = "Tambahan Biaya Solar";

    const OPEN      = "OPEN";
    const CONFIRMED = "CONFIRMED";
    const CLOSED    = "CLOSED";
}
