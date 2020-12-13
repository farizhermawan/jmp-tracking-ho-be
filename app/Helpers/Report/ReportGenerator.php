<?php

namespace App\Helpers\Report;

use App\Enums\HttpStatus;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportGenerator
{
  protected function export($template = "blank", $param = null)
  {
    $hash = $this->generateHash();
    $template = storage_path("app/report-template/{$template}.xlsx");

    try {
      $spreadsheet = $this->impl(IOFactory::load($template), $param);

      $writer = new Xlsx($spreadsheet);
      $writer->save(storage_path("app/public/{$hash}.xlsx"));

      return response()->json(['message' => "success", "hash" => $hash], HttpStatus::SUCCESS);
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage(), 'stacktrace' => $e->getTrace()], HttpStatus::ERROR);
    }
  }

  protected function generateHash() {
    return md5(Carbon::now()->timestamp);
  }

  protected function impl(Spreadsheet $spreadsheet, $param) {
    // Report implementation
    return $spreadsheet;
  }
}
