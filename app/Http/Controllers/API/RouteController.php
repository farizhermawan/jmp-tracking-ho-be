<?php

namespace App\Http\Controllers\API;

use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\Route;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RouteController extends Controller
{
  public function getAll()
  {
    $routes = Route::all();
    return response()->json(['data' => $routes], HttpStatus::SUCCESS);
  }

  public function addRoute(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $route = new Route();
    $route->name = $param->name;
    $route->additional_data = $param->additional_data;
    $route->created_by = $this->user->name;
    $route->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function toggleRoute(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $route = Route::whereId($param->id)->first();
    if (!$route) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $route->flag_active = !$route->flag_active;
    $route->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function updateRoute(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $route = Route::whereId($param->id)->first();
    if (!$route) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $route->name = $param->name;
    $route->additional_data = $param->additional_data;
    $route->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function removeRoute(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $route = Route::whereId($param->id)->first();
    if (!$route) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    try {
      $route->delete();
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

    $template = storage_path("app/report-template/route.xlsx");

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $records = Route::all();

      $startRow = 4;
      $sheet->insertNewRowBefore($startRow + 1, $records->count() - 1);

      $no = 0;
      foreach ($records as $record) {
        $sheet->setCellValue("A" . ($startRow + $no), $no + 1);
        $sheet->setCellValue("B" . ($startRow + $no), $record->name);
        if (isset($record->additional_data)) {
          if (isset($record->additional_data['price'])) $sheet->setCellValue("C" . ($startRow + $no), $record->additional_data['price']);
          if (isset($record->additional_data['cost'])) $sheet->setCellValue("D" . ($startRow + $no), $record->additional_data['cost']);
          if (isset($record->additional_data['commission'])) $sheet->setCellValue("E" . ($startRow + $no), $record->additional_data['commission']);
          if (isset($record->additional_data['commission2'])) $sheet->setCellValue("F" . ($startRow + $no), $record->additional_data['commission2']);
          if (isset($record->additional_data['solar_cost'])) $sheet->setCellValue("G" . ($startRow + $no), $record->additional_data['solar_cost']);
        }
        $sheet->setCellValue("H" . ($startRow + $no), $record->flag_active ? 'Y' : 'N');
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
