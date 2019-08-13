<?php

namespace App\Http\Controllers\API;

use App\Kenek;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * KenekController
 *
 * @property \App\User $user
 */
class KenekController extends Controller
{
  public function getAll()
  {
    $kenek = Kenek::all();
    return response()->json(['data' => $kenek], HttpStatus::SUCCESS);
  }

  public function addKenek(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $kenek = new Kenek();
    $kenek->name = $param->name;
    $kenek->additional_data = isset($param->additional_data) ? $param->additional_data : null;
    $kenek->created_by = $this->user->name;
    $kenek->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function toggleKenek(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $kenek = Kenek::whereId($param->id)->first();
    if (!$kenek) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $kenek->flag_active = !$kenek->flag_active;
    $kenek->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function updateKenek(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $kenek = Kenek::whereId($param->id)->first();
    if (!$kenek) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $kenek->name = $param->name;
    $kenek->additional_data = isset($param->additional_data) ? $param->additional_data : null;
    $kenek->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function removeKenek(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $kenek = Kenek::whereId($param->id)->first();
    if (!$kenek) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    try {
      $kenek->delete();
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }
}
