<?php

namespace App\Helpers\Report;

use App\Enums\Common;
use App\Transaction;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransactionDetailReport extends ReportGenerator
{
  protected function impl(Spreadsheet $spreadsheet, $param)
  {
    setlocale(LC_TIME, 'Indonesian');
    Carbon::setLocale("id");

    $sheetSupir = $spreadsheet->getSheetByName("komisi supir");
    $sheetKenek = $spreadsheet->getSheetByName("komisi kenek");
    $sheetSummary = $spreadsheet->getSheetByName("summary");

    $records = Transaction::getTransactions($param->dateStart, $param->dateEnd, $param->status);

    $startRow = 2;
    $minRow = 5;
    $totalRow = $records->count();
    if ($totalRow < $minRow) $totalRow = $minRow;
    if ($totalRow > $minRow) {
      $diffCount = $totalRow - $minRow;
      $sheetSupir->insertNewRowBefore(11, $diffCount);
      $sheetKenek->insertNewRowBefore(11, $diffCount);
    }

    $records = $records->all();
    usort($records, function($a, $b) {
      return $a->driver_name <=> $b->driver_name;
    });

    $no = 0;
    $lastDriver = null;
    $totalRecord = null;
    foreach ($records as $record) {
      if ($lastDriver == null) {
        $lastDriver = $record->driver_name;
        $totalRecord = 0;
      }
      $sheetSupir->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
      $sheetSupir->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
      $sheetSupir->setCellValue("C" . ($startRow + $no), $record->driver_name);
      $sheetSupir->setCellValue("D" . ($startRow + $no), $record->police_number);
      $sheetSupir->setCellValue("E" . ($startRow + $no), $record->customer_name);
      $sheetSupir->setCellValue("F" . ($startRow + $no), $record->route);
      $sheetSupir->setCellValue("G" . ($startRow + $no), $record->commission);
      if ($record->solar_cost > 0) {
        $sheetSupir->setCellValue("H" . ($startRow + $no), $record->solar_cost);
      } else {
        foreach ($record->cost_entries as $cost_entry) {
          if ($cost_entry['item'] == Common::BIAYA_SOLAR) {
            $sheetSupir->setCellValue("H" . ($startRow + $no), $cost_entry['value']);
            break;
          }
        }
      }

      $totalRecord++;
      $no++;
      if ($lastDriver != $record->driver_name || $no == $totalRow) {
        $lastDriver = null;
        $end = $no;
        $start = $end - $totalRecord + 1;
        if ($start < $startRow) $start = $startRow;
        if ($end == $totalRow) $end++;
        $sheetSupir->mergeCellsByColumnAndRow(10, $start, 10, $end);
        $sheetSupir->setCellValue("J" . ($start), sprintf("=SUM(G%s:I%s)", $start, $end));
        $sheetSupir->getStyle("A{$start}:J{$end}")->applyFromArray([
          'borders' => [
            'outline' => [
              'borderStyle' => Border::BORDER_THIN
            ]
          ]
        ]);
      }
    }
    $sheetSummary->setCellValue("B3", "=SUM('komisi supir'!G{$startRow}:G{$end})");
    $sheetSummary->setCellValue("B4", "=SUM('komisi supir'!H{$startRow}:H{$end})");
    $sheetSummary->setCellValue("B6", "=SUM('komisi supir'!I{$startRow}:I{$end})");

    usort($records, function($a, $b) {
      return $a->kenek_name <=> $b->kenek_name;
    });

    $no = 0;
    $lastKenek = null;
    $totalRecord = null;
    $totalSkipped = 0;
    foreach ($records as $record) {
      if ($record->kenek_name == "") {
        $totalSkipped++;
        continue;
      }
      if ($lastKenek == null) {
        $lastKenek = $record->kenek_name;
        $totalRecord = 0;
      }
      $sheetKenek->setCellValue("A" . ($startRow + $no), $record->created_at->toDateString());
      $sheetKenek->setCellValue("B" . ($startRow + $no), $record->created_at->toTimeString());
      $sheetKenek->setCellValue("C" . ($startRow + $no), $record->kenek_name);
      $sheetKenek->setCellValue("D" . ($startRow + $no), $record->driver_name);
      $sheetKenek->setCellValue("E" . ($startRow + $no), $record->police_number);
      $sheetKenek->setCellValue("F" . ($startRow + $no), $record->customer_name);
      $sheetKenek->setCellValue("G" . ($startRow + $no), $record->route);
      $sheetKenek->setCellValue("H" . ($startRow + $no), $record->commission2);
      $totalRecord++;
      $no++;
      if ($lastKenek != $record->kenek_name || $no + $totalSkipped == $totalRow) {
        $lastKenek = null;
        $end = $no;
        $start = $end - $totalRecord + 1;
        if ($start < $startRow) $start = $startRow;
        if ($end == $totalRow - $totalSkipped) $end++;
        $sheetKenek->mergeCellsByColumnAndRow(10, $start, 10, $end);
        $sheetKenek->setCellValue("J" . ($start), sprintf("=SUM(H%s:I%s)", $start, $end));
        $sheetKenek->getStyle("A{$start}:J{$end}")->applyFromArray([
          'borders' => [
            'outline' => [
              'borderStyle' => Border::BORDER_THIN
            ]
          ]
        ]);
      }
    }
    $sheetSummary->setCellValue("B5", "=SUM('komisi kenek'!H{$startRow}:H{$end})");
    $sheetSummary->setCellValue("B7", "=SUM('komisi kenek'!I{$startRow}:I{$end})");

    $spreadsheet->setActiveSheetIndex(0);

    return $spreadsheet;
  }

  public function generate($param)
  {
    return $this->export("transaksi-detail", $param);
  }
}
