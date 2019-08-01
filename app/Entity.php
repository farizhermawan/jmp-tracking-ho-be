<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * App\Entity
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Entity whereName($value)
 * @mixin \Eloquent
 */
class Entity extends Model
{
    protected $table = 'entities';

    public $timestamps = false;
}
