<?php

namespace App\Http\Controllers\API;

use App\Driver;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * DriverController
 *
 * @property \App\User $user
 */
class DriverController extends Controller
{
  public function getAll()
  {
    $drivers = Driver::all();
    return response()->json(['data' => $drivers], HttpStatus::SUCCESS);
  }

  public function addDriver(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $driver = new Driver();
    $driver->name = $param->name;
    $driver->additional_data = isset($param->additional_data) ? $param->additional_data : null;
    $driver->created_by = $this->user->name;
    $driver->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function toggleDriver(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $driver = Driver::whereId($param->id)->first();
    if (!$driver) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $driver->flag_active = !$driver->flag_active;
    $driver->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function updateDriver(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $driver = Driver::whereId($param->id)->first();
    if (!$driver) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $driver->name = $param->name;
    $driver->additional_data = isset($param->additional_data) ? $param->additional_data : null;
    $driver->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function removeDriver(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $driver = Driver::whereId($param->id)->first();
    if (!$driver) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    try {
      $driver->delete();
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }
}
