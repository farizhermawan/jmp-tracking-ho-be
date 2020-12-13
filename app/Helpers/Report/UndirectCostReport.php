<?php

namespace App\Helpers\Report;

use App\UndirectCost;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class UndirectCostReport extends ReportGenerator
{
  protected function impl(Spreadsheet $spreadsheet, $param)
  {
    $categories = ["Pribadi", "Kendaraan", "Rumah Tangga", "Lain-lain"];
    foreach ($categories as $category) {
      $sheet = $spreadsheet->getSheetByName($category);

      $items = UndirectCost::getTransactions($category, "Semua", $param->dateStart, $param->dateEnd);
      $row = 1;
      foreach ($items as $item) {
        $row++;
        $sheet->setCellValue("A" . $row, $item->created_at->toDateString());
        $sheet->setCellValue("B" . $row, $item->created_at->toTimeString());
        if (!empty($item->additional_data['police_number'])) $sheet->setCellValue("C" . $row, $item->additional_data['police_number']);
        if (!empty($item->additional_data['driver'])) $sheet->setCellValue("D" . $row, $item->additional_data['driver']);
        $sheet->setCellValue("E" . $row, $item->category);
        $sheet->setCellValue("F" . $row, $item->note);
        $sheet->setCellValue("G" . $row, $item->total_cost);
      }

    }

    return $spreadsheet;
  }

  public function generate($param)
  {
    return $this->export("undirect-cost", $param);
  }
}
