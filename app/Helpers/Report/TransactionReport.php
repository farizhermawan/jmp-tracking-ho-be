<?php

namespace App\Helpers\Report;

use App\Enums\Common;
use App\Transaction;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class TransactionReport extends ReportGenerator
{
  protected function impl(Spreadsheet $spreadsheet, $param)
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");

    $sheet = $spreadsheet->getActiveSheet();

    $records = Transaction::getTransactions($param->dateStart, $param->dateEnd, $param->status);

    $startRow = 2;
    $minRow = 5;
    $totalRow = $records->count();
    if ($totalRow < $minRow) $totalRow = $minRow;
    if ($totalRow > $minRow) {
      $diffCount = $totalRow - $minRow;
      $sheet->insertNewRowBefore(11, $diffCount);
    }
    $no = 0;
    foreach ($records as $record) {
      $sheet->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
      $sheet->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
      $sheet->setCellValue("C" . ($startRow + $no), $record->driver_name);
      $sheet->setCellValue("D" . ($startRow + $no), $record->kenek_name);
      $sheet->setCellValue("E" . ($startRow + $no), $record->police_number);
      $sheet->setCellValue("F" . ($startRow + $no), $record->container_size);
      $sheet->setCellValue("G" . ($startRow + $no), $record->container_no);
      $sheet->setCellValue("H" . ($startRow + $no), $record->customer_name);
      $sheet->setCellValue("I" . ($startRow + $no), $record->subcustomer_name);
      $sheet->setCellValue("J" . ($startRow + $no), $record->depo_mt);
      $sheet->setCellValue("K" . ($startRow + $no), $record->route);
      $sheet->setCellValue("L" . ($startRow + $no), $record->commission);
      $sheet->setCellValue("M" . ($startRow + $no), $record->commission2);
      $otherCostName = [];
      $otherCostColumn = ['Z', 'Y', 'X', 'W', 'V', 'U', 'T', 'S', 'R', 'Q'];
      foreach ($record->cost_entries as $cost_entry) {
        if ($cost_entry['item'] == Common::UANG_JALAN) $sheet->setCellValue("N" . ($startRow + $no), $cost_entry['value']);
        else if ($cost_entry['item'] == Common::BIAYA_SOLAR) $sheet->setCellValue("O" . ($startRow + $no), $cost_entry['value']);
        else {
          $otherCostName[] = $cost_entry['item'];
          $sheet->setCellValue(array_pop($otherCostColumn) . ($startRow + $no), $cost_entry['value']);
        }
      }
      if ($record->solar_cost > 0) {
        $sheet->setCellValue("O" . ($startRow + $no), $record->solar_cost);
      }
      $sheet->setCellValue("P" . ($startRow + $no), implode(", ", $otherCostName));

      $no++;
    }

    return $spreadsheet;
  }

  public function generate($param)
  {
    return $this->export("transaksi", $param);
  }
}
