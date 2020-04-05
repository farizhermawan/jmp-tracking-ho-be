<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Customer
 *
 * @property int $id
 * @property string $name
 * @property string $flag_active
 * @property string|null $additional_data
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer whereName($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Customer query()
 */
class Customer extends Model
{
  protected $table = 'customers';
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
