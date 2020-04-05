<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Transaction
 *
 * @property int $id
 * @property string $police_number
 * @property string $driver_name
 * @property string|null $kenek_name
 * @property string $customer_name
 * @property string|null $container_size
 * @property string $route
 * @property int $commission
 * @property int $commission2
 * @property int $solar_cost
 * @property int $total_cost
 * @property array $cost_entries
 * @property array|null $post_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property array|null $additional_data
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCommission2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereContainerSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCostEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereDriverName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereKenekName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction wherePoliceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereSolarCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereTotalCost($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
  protected $table = 'transaction';
  protected $dates = ['created_at'];
  protected $casts = [
    'cost_entries' => 'array',
    'post_id' => 'array',
    'additional_data' => 'json'
  ];

  public $timestamps = false;

  static function getTransactions($dateFrom, $dateTo, $status = "Semua"){
    $records = Transaction::whereDate('created_at', '>=', $dateFrom)
      ->whereDate('created_at', '<=', $dateTo);
    if ($status != "Semua") {
      $records = $records->whereStatus($status == 'Lengkap' ? 'closed' : 'open');
    } else {
      $records = $records->where('status', '<>', 'plan');
    }
    $items = $records->orderBy('created_at')->get();
    return $items;
  }

  static function getTransactionsForMonitor($date){
    $records = Transaction::whereDate('created_at', '>=', $date)
      ->whereDate('created_at', '<=', $date);
    $items = $records->get();
    return $items;
  }
}
