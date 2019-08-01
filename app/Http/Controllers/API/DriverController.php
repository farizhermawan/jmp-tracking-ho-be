<?php

namespace App\Http\Controllers\API;

use App\Driver;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
    $driver->additional_data = $param->additional_data;
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
    $driver->additional_data = $param->additional_data;
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

  public function export()
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");
    $this->user = \Auth::user();

    $now = Carbon::now()->addMonth(1);
    $hash = md5($now->timestamp);

    $template = storage_path("app/report-template/driver.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $records = Driver::all();

      $startRow = 4;
      $sheet->insertNewRowBefore($startRow + 1, $records->count() - 1);

      $no = 0;
      foreach ($records as $record) {
        $sheet->setCellValue("A" . ($startRow + $no), $no + 1);
        $sheet->setCellValue("B" . ($startRow + $no), $record->name);
        $sheet->setCellValue("C" . ($startRow + $no), $record->flag_active ? 'Y' : 'N');
        $no++;
      }

      $writer = new Xlsx($spreadsheet);
      $writer->save(storage_path("app/public/{$hash}.xlsx"));

      return response()->json(['message' => "success", "hash" => $hash], HttpStatus::SUCCESS);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }
  }
}
