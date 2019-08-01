<?php

namespace App\Http\Controllers\API;

use App\Enums\DebitCredit;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\Enums\RefCode;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\RequestBallance;
use DB;
use Illuminate\Http\Request;

/**
 * BallanceController
 *
 * @property \App\User $user
 */
class BallanceController extends Controller
{
  public function getBallance($id)
  {
    $ballance = FinancialRecord::getBallance(Entity::get($id));
    return response()->json(['ballance' => $ballance], HttpStatus::SUCCESS);
  }

  public function addBallance(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    try {
      DB::transaction(function () use ($param) {
        // record request ballance history
        $requestBallance = new RequestBallance();
        $requestBallance->entity = Entity::get($param->entity->id);
        $requestBallance->amount = $param->amount;
        $requestBallance->created_by = $this->user->name;
        $requestBallance->save();

        $postId = [];
        if ($param->amount > 0) {
          $postId[] = FinancialRecord::postFinancialRecord(RefCode::BALLANCE, $requestBallance->id, "Penambahan Saldo", DebitCredit::DEBIT, $param->amount, $requestBallance->entity);
        } else {
          $param->amount = abs($param->amount);
          $postId[] = FinancialRecord::postFinancialRecord(RefCode::BALLANCE, $requestBallance->id, "Pengurangan Saldo", DebitCredit::CREDIT, abs($param->amount), $requestBallance->entity);
        }
        $requestBallance->post_id = $postId;
        $requestBallance->save();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    $ballance = FinancialRecord::getBallance(Entity::get($param->entity->id));
    return response()->json(['message' => 'success', 'ballance' => $ballance], HttpStatus::SUCCESS);
  }
}
