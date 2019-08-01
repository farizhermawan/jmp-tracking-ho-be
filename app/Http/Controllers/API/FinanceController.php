<?php

namespace App\Http\Controllers\API;

use App\Enums\DebitCredit;
use App\Enums\Entity;
use App\Enums\HttpStatus;
use App\Enums\RefCode;
use App\FinancialRecord;
use App\Http\Controllers\Controller;
use App\RequestBallance;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * FinanceController
 *
 * @property \App\User $user
 */

class FinanceController extends Controller
{
    public function getFinance(Request $request)
    {
        $param = json_decode($request->getContent());
        $records = FinancialRecord::getFinance(Entity::get($param->entity->id), $param->dateStart, $param->dateEnd);
        return response()->json(['data' => $records], HttpStatus::SUCCESS);
    }

    public function addBallance(Request $request)
    {
        $this->user = \Auth::user();
        $param = json_decode($request->getContent());

        try {
            DB::transaction(function () use ($param) {
                // record request ballance history
                $requestBallance = new RequestBallance();
                $requestBallance->entity = Entity::get($param->entity->id);
                $requestBallance->amount = $param->amount;
                $requestBallance->created_by = $this->user->name;
                $requestBallance->save();

                // is root ballance or not
                if ($param->entity->id == Entity::HO) {
                    FinancialRecord::postFinancialRecord(RefCode::BALLANCE, $requestBallance->id, "Isi saldo utama", DebitCredit::DEBIT, $param->amount, Entity::HO);
                }
                else {
                    FinancialRecord::postFinancialRecord(RefCode::BALLANCE, $requestBallance->id, "Isi saldo {$param->entity->name}", DebitCredit::DEBIT, $param->amount, Entity::UNKNOWN);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['message'=> $e->getMessage()], HttpStatus::ERROR);
        }

        $ballance = FinancialRecord::getBallance(Entity::get($param->entity->id));
        return response()->json(['message' => 'success', 'ballance' => $ballance], HttpStatus::SUCCESS);
    }

    public function export(Request $request)
    {
        $hash = md5(time());
        $param = json_decode($request->getContent());
        $template = storage_path("app/report-template/finance.xlsx");

        try {
            $spreadsheet = IOFactory::load($template);
            $sheet = $spreadsheet->getActiveSheet();

            $row = 2;
            $data = FinancialRecord::getFinance(Entity::get($param->entity->id), $param->dateStart, $param->dateEnd);

            $displayDateFormat = "Y-m-d";
            $startDate = Carbon::createFromFormat("Y-m-d", $param->dateStart);
            $sheet->setCellValue("A" . $row, $startDate->format($displayDateFormat));
            $sheet->setCellValue("C" . $row, "Saldo");
            $sheet->setCellValue("F" . $row, $data['ballance']);
            foreach ($data['records'] as $item) {
                $row++;
                $sheet->setCellValue("A" . $row, $item->posted_at->format($displayDateFormat));
                $sheet->setCellValue("B" . $row, $item->ref_code);
                $sheet->setCellValue("C" . $row, $item->message);
                $sheet->setCellValue(($item->type == DebitCredit::CREDIT ? "D" : "E") . $row, $item->amount);
                $sheet->setCellValue("F" . $row, $item->ballance);
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
