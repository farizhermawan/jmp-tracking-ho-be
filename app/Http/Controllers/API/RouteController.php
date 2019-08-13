<?php

namespace App\Http\Controllers\API;

use App\Kenek;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\Route;
use Illuminate\Http\Request;

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
}
