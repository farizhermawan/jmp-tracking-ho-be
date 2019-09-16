<?php

namespace App\Http\Controllers\API;

use App\Enums\HttpStatus;
use App\Vehicle;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * VehicleController
 *
 * @property \App\User $user
 */
class VehicleController extends Controller
{
  public function getAll()
  {
    $vehicles = Vehicle::all();
    return response()->json(['data' => $vehicles], HttpStatus::SUCCESS);
  }

  public function addVehicle(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $vehicle = new Vehicle();
    $vehicle->police_number = $param->police_number;
    $vehicle->additional_data = $param->additional_data;
    $vehicle->created_by = $this->user->name;
    $vehicle->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function toggleVehicle(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $vehicle = Vehicle::whereId($param->id)->first();
    if (!$vehicle) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $vehicle->flag_active = !$vehicle->flag_active;
    $vehicle->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function updateVehicle(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $vehicle = Vehicle::whereId($param->id)->first();
    if (!$vehicle) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $vehicle->police_number = $param->police_number;
    $vehicle->additional_data = $param->additional_data;
    $vehicle->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function removeVehicle(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $vehicle = Vehicle::whereId($param->id)->first();
    if (!$vehicle) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    try {
      $vehicle->delete();
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function export()
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");
    $this->user = \Auth::user();

    $now = Carbon::now()->addMonth(1);
    $hash = md5($now->timestamp);

    $template = storage_path("app/report-template/vehicle.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $records = Vehicle::all();

      $startRow = 4;
      $sheet->insertNewRowBefore($startRow + 1, $records->count() - 1);

      $no = 0;
      foreach ($records as $record) {
        $sheet->setCellValue("A" . ($startRow + $no), $no + 1);
        $sheet->setCellValue("B" . ($startRow + $no), $record->police_number);
        if (isset($record->additional_data)) {
          $sheet->setCellValue("C" . ($startRow + $no), $record->additional_data['type']);
        }
        $sheet->setCellValue("D" . ($startRow + $no), $record->flag_active ? 'Y' : 'N');
        $no++;
      }

      $writer = new Xlsx($spreadsheet);
      $writer->save(storage_path("app/public/{$hash}.xlsx"));

      return response()->json(['message' => "success", "hash" => $hash], HttpStatus::SUCCESS);
    }
    catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
    catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
    catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
  }
}
