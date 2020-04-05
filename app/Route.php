<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * App\Route
 *
 * @property int $id
 * @property string $name
 * @property string $flag_active
 * @property string|null $additional_data
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route whereName($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Route query()
 */
class Route extends Model
{
    protected $table = 'routes';
    protected $dates = ['created_at'];
    public $timestamps = false;

    protected $casts = [
        'additional_data' => 'json'
    ];

    protected $fillable = [
        'name', 'flag_active', 'additional_data',
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
