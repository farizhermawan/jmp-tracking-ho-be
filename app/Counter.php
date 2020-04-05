<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Counter
 *
 * @property int $id
 * @property string $date
 * @property string $type
 * @property string $field
 * @property int $value
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter whereValue($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Counter query()
 */
class Counter extends Model
{
    protected $table = 'counter';
    public $timestamps = false;

    static function increase($type, $field, $date = null)
    {
        if($date == null) $date = Carbon::now();
        $lastRecord = Counter::where("date", "=", $date->toDateString())->whereType($type)->whereField($field)->orderBy('id', 'desc')->first();
        if (!$lastRecord) {
            $lastRecord = new Counter();
            $lastRecord->type = $type;
            $lastRecord->field = $field;
            $lastRecord->date = $date;
            $lastRecord->value = 0;
        }
        $lastRecord->value++;
        $lastRecord->save();
    }

  static function decrease($type, $field, $date = null)
  {
    if($date == null) $date = Carbon::now();
    $lastRecord = Counter::where("date", "=", $date->toDateString())->whereType($type)->whereField($field)->orderBy('id', 'desc')->first();
    if (!$lastRecord) {
      $lastRecord = new Counter();
      $lastRecord->type = $type;
      $lastRecord->field = $field;
      $lastRecord->date = $date;
      $lastRecord->value = 0;
    }
    $lastRecord->value--;
    $lastRecord->save();
  }
}
