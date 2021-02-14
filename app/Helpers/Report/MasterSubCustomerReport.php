<?php

namespace App\Helpers\Report;

use App\Models\MasterData;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MasterSubCustomerReport extends ReportGenerator
{
  protected function impl(Spreadsheet $spreadsheet, $param)
  {
    $sheet = $spreadsheet->getActiveSheet();
    $records = MasterData::whereGroup("SubCustomer")->get();
    $startRow = 4;
    $sheet->insertNewRowBefore($startRow + 1, $records->count() - 1);
    $no = 0;
    foreach ($records as $record) {
      $sheet->setCellValue("A" . ($startRow + $no), $no + 1);
      $sheet->setCellValue("B" . ($startRow + $no), $record->name);
      $sheet->setCellValue("C" . ($startRow + $no), $record->flag_active ? 'Y' : 'N');
      $no++;
    }
    return $spreadsheet;
  }

  public function generate($param)
  {
    return $this->export("master-sub-customer", $param);
  }
}
