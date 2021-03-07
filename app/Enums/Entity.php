<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Entity extends Enum
{
    const HO = "01";
    const BANK = "02";
    const UNKNOWN = "00";

    static function get($entity) {
        if (is_numeric($entity)) $entity = str_pad($entity, 2, "0", STR_PAD_LEFT);
        if ($entity == Entity::HO) return Entity::HO;
        if ($entity == Entity::BANK) return Entity::BANK;
        return Entity::UNKNOWN;
    }
}
