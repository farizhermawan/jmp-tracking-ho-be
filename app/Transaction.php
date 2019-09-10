<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Transaction
 *
 * @property int $id
 * @property string $police_number
 * @property string $driver_name
 * @property string $customer_name
 * @property string|null $container_size
 * @property string $route
 * @property int $commission
 * @property int $total_cost
 * @property array $cost_entries
 * @property array|null $post_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property array|null $additional_data
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereContainerSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCostEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereDriverName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction wherePoliceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereTotalCost($value)
 * @mixin \Eloquent
 * @property string $kenek_name
 * @property int $commission2
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereCommission2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereKenekName($value)
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Transaction whereStatus($value)
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

    static function getTransactions($dateFrom, $dateTo){
        $records = Transaction::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
        $items = $records->get();
        return $items;
    }
}
