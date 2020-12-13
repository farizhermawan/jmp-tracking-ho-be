<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\UndirectCost
 *
 * @property int $id
 * @property string|null $category
 * @property string $subcategory
 * @property string $note
 * @property int $total_cost
 * @property array $additional_data
 * @property array|null $post_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereSubcategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UndirectCost whereTotalCost($value)
 * @mixin \Eloquent
 */
class UndirectCost extends Model
{
  public $timestamps = false;
  protected $table = 'undirect_cost';
  protected $dates = ['created_at'];
  protected $casts = [
    'additional_data' => 'array',
    'post_id' => 'array'
  ];

  static function getTransactions($category, $subcategory, $dateFrom, $dateTo)
  {
    $records = UndirectCost::whereDate('created_at', '>=', $dateFrom)
      ->whereDate('created_at', '<=', $dateTo)
      ->whereCategory($category);
    if ($subcategory != 'Semua') $records = $records->whereSubcategory($subcategory);
    $items = $records->get();
    return $items;
  }

}
