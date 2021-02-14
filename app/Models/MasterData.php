<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MasterData
 *
 * @property int $id
 * @property string $group
 * @property string $name
 * @property array|null $additional_data
 * @property bool $flag_active
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData query()
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereFlagActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MasterData whereName($value)
 * @mixin \Eloquent
 */
class MasterData extends Model
{
  use Auditable;

  protected $table = 'master_data';
  protected $dates = ['created_at'];
  public $timestamps = false;

  protected $casts = [
    'flag_active' => 'boolean',
    'additional_data' => 'json'
  ];

  protected $fillable = [
    'name',
    'flag_active',
    'additional_data',
  ];

  protected $hidden = [
    'created_by',
    'created_at',
  ];
}
