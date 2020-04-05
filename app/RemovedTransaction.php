<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RemovedTransaction
 *
 * @property int $id
 * @property int $ref
 * @property string|null $source
 * @property string|null $additional_data
 * @property array|null $post_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction whereSource($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RemovedTransaction query()
 */
class RemovedTransaction extends Model
{
    protected $table = 'removed_transaction';
    protected $dates = ['created_at'];
    protected $casts = [
        'post_id' => 'array'
    ];
    public $timestamps = false;
}
