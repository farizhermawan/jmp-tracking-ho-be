<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\VehicleCost
 *
 * @property int $id
 * @property string $category
 * @property string $note
 * @property int $total_cost
 * @property array $additional_data
 * @property array|null $post_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VehicleCost whereTotalCost($value)
 * @mixin \Eloquent
 */
class VehicleCost extends Model
{
  protected $table = 'vehicle_cost';
  protected $dates = ['created_at'];
  protected $casts = [
    'additional_data' => 'array',
    'post_id' => 'array'
  ];
  public $timestamps = false;

  static function getTransactions($category, $dateFrom, $dateTo)
  {
    $records = VehicleCost::whereDate('created_at', '>=', $dateFrom)
      ->whereDate('created_at', '<=', $dateTo);
    if ($category != 'Semua') $records = $records->whereCategory($category);
    $items = $records->get();
    return $items;
  }

}
