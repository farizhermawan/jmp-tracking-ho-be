<?php

namespace App\Http\Controllers\API;

use App\Customer;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\Route;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
  public function getAll()
  {
    $routes = Customer::all();
    return response()->json(['data' => $routes], HttpStatus::SUCCESS);
  }

  public function addCustomer(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $route = new Customer();
    $route->name = $param->name;
    $route->additional_data = $param->additional_data;
    $route->created_by = $this->user->name;
    $route->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function toggleCustomer(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $customer = Customer::whereId($param->id)->first();
    if (!$customer) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $customer->flag_active = !$customer->flag_active;
    $customer->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function updateCustomer(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $customer = Customer::whereId($param->id)->first();
    if (!$customer) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    $customer->name = $param->name;
    $customer->additional_data = $param->additional_data;
    $customer->save();

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }

  public function removeCustomer(Request $request)
  {
    $this->user = \Auth::user();
    $param = json_decode($request->getContent());

    $customer = Customer::whereId($param->id)->first();
    if (!$customer) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

    try {
      $customer->delete();
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
    }

    return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
  }
}
