<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

/**
 * App\RequestBallance
 *
 * @property int $id
 * @property int $amount
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance whereId($value)
 * @mixin \Eloquent
 * @property string $entity
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance whereEntity($value)
 * @property string|null $post_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RequestBallance query()
 */
class RequestBallance extends Model
{
    protected $table = 'request_ballance';
    protected $dates = ['created_at'];
    protected $casts = ['post_id' => 'array'];
    public $timestamps = false;
}
