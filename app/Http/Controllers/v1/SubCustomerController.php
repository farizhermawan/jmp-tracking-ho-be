<?php

namespace App\Http\Controllers\v1;

use App\Helpers\Report\MasterSubCustomerReport;
use App\Http\RestResponse;
use App\Http\Controllers\Controller;
use App\Models\MasterData;
use Illuminate\Http\Request;

class SubCustomerController extends Controller
{
  private $entity = "SubCustomer";

  public function index()
  {
    return RestResponse::data(['data' => MasterData::whereGroup($this->entity)->get()]);
  }

  public function store(Request $request)
  {
    $data = new MasterData($request->all());
    $data->group = $this->entity;
    $data->save();
    return RestResponse::created($data);
  }

  public function show(MasterData $sub_customer)
  {
    $data = $sub_customer;
    if ($data->group != $this->entity) return RestResponse::error("missmatch entity");
    return RestResponse::data($data);
  }

  public function update(Request $request, MasterData $sub_customer)
  {
    $data = $sub_customer;
    if ($data->group != $this->entity) return RestResponse::error("missmatch entity");
    $data->update($request->all());
    $data->save();
    return RestResponse::updated($data);
  }

  public function destroy(MasterData $sub_customer)
  {
    $data = $sub_customer;
    if ($data->group != $this->entity) return RestResponse::error("missmatch entity");
    try {
      $data->delete();
    } catch (\Exception $e) {
      return RestResponse::error($e->getMessage());
    }
    return RestResponse::deleted($data);
  }

  public function export(Request $request)
  {
    $param = json_decode($request->getContent());
    $report = new MasterSubCustomerReport();
    return $report->generate($param);
  }
}
