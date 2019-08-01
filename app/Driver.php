<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * App\Driver
 *
 * @property int $id
 * @property string $name
 * @property string $flag_active
 * @property string|null $additional_data
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Driver whereName($value)
 * @mixin \Eloquent
 */
class Driver extends Model
{
    protected $table = 'drivers';
    protected $dates = ['created_at'];
    public $timestamps = false;

    protected $fillable = [
        'name', 'flag_active', 'additional_data',
    ];

    protected $hidden = [
        'created_by', 'created_at',
    ];

    protected $casts = [
        'additional_data' => 'json'
    ];

    public function setFlagActiveAttribute($value)
    {
        $this->attributes['flag_active'] = $value ? "Y" : "N";
    }

    public function getFlagActiveAttribute($value)
    {
        return $value == "Y";
    }
}
