<?php

namespace App\Http\Controllers\v1;

use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\Models\MasterData;
use Illuminate\Http\Request;

class DepoMTController extends Controller
{
  private $entity = "depo_mt";

  public function index()
  {
    return response()->json(['data' => MasterData::whereGroup($this->entity)->get()], HttpStatus::SUCCESS);
  }

  public function store(Request $request)
  {
    $data = new MasterData($request->all());
    $data->group = $this->entity;
    $data->save();
    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function show(MasterData $depo_mt)
  {
    $data = $depo_mt;
    if ($data->group != $this->entity) response()->json(["message" => "missmatch entity"], HttpStatus::ERROR);
    return response()->json($data, HttpStatus::SUCCESS);
  }

  public function update(Request $request, MasterData $depo_mt)
  {
    $data = $depo_mt;
    if ($data->group != $this->entity) response()->json(["message" => "missmatch entity"], HttpStatus::ERROR);
    $data->update($request->all());
    $data->save();
    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function destroy(MasterData $depo_mt)
  {
    $data = $depo_mt;
    if ($data->group != $this->entity) response()->json(["message" => "missmatch entity"], HttpStatus::ERROR);
    try {
      $data->delete();
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }
}
