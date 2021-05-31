<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class HttpStatus extends Enum
{
    const SUCCESS = 200;
    const BADREQUEST = 400;
    const UNAUTHORIZED = 401;
    const NOTFOUND = 404;
    const ERROR = 500;
}
