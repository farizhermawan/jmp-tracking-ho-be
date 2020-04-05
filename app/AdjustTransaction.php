<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\AdjustTransaction
 *
 * @property int $id
 * @property int $ref
 * @property array|null $post_id
 * @property array $old_value
 * @property array $new_value
 * @property int $discrepancy
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereDiscrepancy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereNewValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereOldValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction whereRef($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AdjustTransaction query()
 */
class AdjustTransaction extends Model
{
    protected $table = 'adjust_transaction';
    protected $dates = ['created_at'];
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'post_id' => 'array'
    ];
    public $timestamps = false;

}
