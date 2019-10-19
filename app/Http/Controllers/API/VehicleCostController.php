<?php

namespace App\Http\Controllers\API;

use DB;
use App\VehicleCost;
use App\Enums\Entity;
use App\Enums\RefCode;
use App\Helpers\ReportDataGrabber;
use App\Enums\HttpStatus;
use App\RemovedTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * UndirectCostController
 *
 * @property \App\User $user
 */
class VehicleCostController extends Controller
{

  public function getTransaksi(Request $request)
  {
    $param = json_decode($request->getContent());
    $records = VehicleCost::getTransactions($param->category, $param->dateStart, $param->dateEnd);
    return response()->json(['message' => 'success', 'data' => $records], HttpStatus::SUCCESS);
  }

  public function saveTransaction(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    try {
      DB::transaction(function () use ($param) {
        $entity = Entity::HO;

        // create new transaction
        $vehicleCost = new VehicleCost();
        $vehicleCost->category = $param->category;
        $vehicleCost->note = $param->note;
        $vehicleCost->total_cost = $param->cost;
        $additional_data = [];
        if (isset($param->driver)) $additional_data['driver'] = $param->driver->name;
        if (isset($param->police_number)) $additional_data['police_number'] = $param->police_number->police_number;
        $vehicleCost->additional_data = $additional_data;
        $vehicleCost->created_by = $this->user->name;
        $vehicleCost->save();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);                // Create new JOT$undirect->created_by = $this->user->name;
    }

    return response()->json(['message' => "success"], HttpStatus::SUCCESS);
  }

  public function remove(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());
    $vehicleCost = VehicleCost::whereId($param->id)->first();
    if (!$vehicleCost) return response()->json(['message' => 'Data tidak ditemukan!'], HttpStatus::SUCCESS);

    try {
      DB::transaction(function () use ($param, $vehicleCost) {
        // Records Remove Transaction History
        $remove = new RemovedTransaction();
        $remove->source = RefCode::UNDIRECT;
        $remove->ref = $vehicleCost->id;
        $remove->additional_data = $vehicleCost->toJson();
        $remove->created_by = $this->user->name;
        $remove->save();

        // Remove the item
        $vehicleCost->delete();
      });
    } catch (\Throwable $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function export(Request $request)
  {
    $hash = md5(time());
    $param = json_decode($request->getContent());
    $template = storage_path("app/report-template/vehicle-cost-report.xlsx");
    $headers = ['Authorization: '.$request->header('authorization')];

    $data = (object) array();
    $data->dateStart = $request->dateStart;
    $data->dateEnd = $request->dateEnd;
    
    $report = new ReportDataGrabber($headers);
    
    // KLARI
    $dataKlari = $report->getDataFromKlari($data);
    
    // HO
    $dataHo = $report->getDataFromHO($data, $request->category);

    // CDP
    $dataCdp = $report->getDataFromCdp($data);

    try {
      $spreadsheet = IOFactory::load($template);
      $sheet = $spreadsheet->getActiveSheet();

      $row = 1;
      foreach ($dataHo as $item) {
        $row++;
        $datetime = explode(' ', $item->created_at);
        $additional = $item->additional_data;
        $sheet->setCellValue("A" . $row, $datetime[0]);
        $sheet->setCellValue("B" . $row, $datetime[1]);
        $sheet->setCellValue("C" . $row, isset($additional->police_number) ? $additional->police_number : "");
        $sheet->setCellValue("D" . $row, isset($additional->employee) ? $additional->employee : "");
        $sheet->setCellValue("E" . $row, $item->category);
        $sheet->setCellValue("F" . $row, $item->note);
        $sheet->setCellValue("G" . $row, $item->total_cost);
        $sheet->setCellValue("H" . $row, 'HO');
      }

      foreach ($dataKlari as $item) {
        $row++;
        $datetime = explode(' ', $item->created_at);
        $additional = $item->additional_data;
        $sheet->setCellValue("A" . $row, $datetime[0]);
        $sheet->setCellValue("B" . $row, $datetime[1]);
        $sheet->setCellValue("C" . $row, isset($additional->police_number) ? $additional->police_number : "");
        $sheet->setCellValue("D" . $row, isset($additional->employee) ? $additional->employee : "");
        $sheet->setCellValue("E" . $row, $item->category);
        $sheet->setCellValue("F" . $row, $item->note);
        $sheet->setCellValue("G" . $row, $item->total_cost);
        $sheet->setCellValue("H" . $row, 'Klari');
      }

      foreach ($dataCdp as $item) {
        $row++;
        $datetime = explode(' ', $item->created_at);
        $additional = $item->additional_data;
        $sheet->setCellValue("A" . $row, $datetime[0]);
        $sheet->setCellValue("B" . $row, $datetime[1]);
        $sheet->setCellValue("C" . $row, isset($additional->police_number) ? $additional->police_number : "");
        $sheet->setCellValue("D" . $row, isset($additional->employee) ? $additional->employee : "");
        $sheet->setCellValue("E" . $row, $item->category);
        $sheet->setCellValue("F" . $row, $item->note);
        $sheet->setCellValue("G" . $row, $item->total_cost);
        $sheet->setCellValue("H" . $row, 'CDP');
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
