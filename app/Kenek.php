<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Kenek
 *
 * @property int $id
 * @property string $name
 * @property string $flag_active
 * @property array|null $additional_data
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Kenek whereName($value)
 * @mixin \Eloquent
 */
class Kenek extends Model
{
    protected $table = 'keneks';
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
