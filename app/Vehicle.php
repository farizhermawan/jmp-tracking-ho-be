<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * App\Vehicle
 *
 * @property int $id
 * @property string $police_number
 * @property string $flag_active
 * @property string|null $additional_data
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle wherePoliceNumber($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Vehicle query()
 */
class Vehicle extends Model
{
    protected $table = 'vehicles';
    protected $dates = ['created_at'];
    public $timestamps = false;

    protected $casts = [
        'additional_data' => 'json'
    ];

    protected $fillable = [
        'police_number', 'flag_active', 'additional_data',
    ];

    protected $hidden = [
        'created_by', 'created_at',
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
