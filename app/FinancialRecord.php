<?php

namespace App;

use App\Enums\DebitCredit;
use App\Helpers\DateTime;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\FinancialRecord
 *
 * @property int $id
 * @property string $ref_code
 * @property int $ref_id
 * @property string $message
 * @property string $type
 * @property int $amount
 * @property int $ballance
 * @property string|null $posted_by
 * @property \Illuminate\Support\Carbon $posted_at
 * @property string $entity
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereBallance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereEntity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereRefCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereRefId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FinancialRecord whereType($value)
 * @mixin \Eloquent
 */
class FinancialRecord extends Model
{
    protected $table = 'financial_record';
    protected $dates = ['posted_at'];
    public $timestamps = false;

    static function postFinancialRecord($refCode, $refId, $message, $type, $amount, $entity)
    {
        $user = \Auth::user();
        $ballance = $type == DebitCredit::DEBIT ? FinancialRecord::getBallance($entity) + $amount : FinancialRecord::getBallance($entity) - $amount;
        $finance = new FinancialRecord();
        $finance->ref_code = $refCode;
        $finance->ref_id = $refId;
        $finance->message = $message;
        $finance->type = $type;
        $finance->amount = $amount;
        $finance->ballance = $ballance;
        $finance->entity = $entity;
        $finance->posted_by = $user->name;
        $finance->save();
        return $finance->id;
    }

    static function getFinance($entity, $dateFrom, $dateTo)
    {
        $records = FinancialRecord::whereEntity($entity)
            ->whereDate('posted_at', '>=', $dateFrom)
            ->whereDate('posted_at', '<=', $dateTo)
            ->orderBy('id', 'asc')
            ->get();
        $initBallance = isset($records[0]) ? FinancialRecord::getInitBallance($entity, $records[0]->id) : 0;
        return ['ballance' => $initBallance, 'records' => $records];
    }

    static function getBallance($entity, $date = null)
    {
        if($date == null) $date = Carbon::now();
        $lastRecord = FinancialRecord::whereEntity($entity)
            ->whereDate('posted_at', '<=', $date->toDateString())
            ->orderBy('id', 'desc')
            ->first();
        return $lastRecord ? $lastRecord->ballance : 0;
    }

    static function getInitBallance($entity, $id)
    {
        $lastRecord = FinancialRecord::whereEntity($entity)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first();
        return $lastRecord ? $lastRecord->ballance : 0;
    }
}
