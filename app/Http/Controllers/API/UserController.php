<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * UserController
 *
 * @property \App\User $user
 */
class UserController extends Controller
{
    public function getAll()
    {
        $users = User::all();
        return response()->json(['data' => $users], HttpStatus::SUCCESS);
    }

    public function addUser(Request $request)
    {
        $this->user = \Auth::user();
        $param = json_decode($request->getContent());

        $user = new User();
        $user->name = $param->name;
        $user->email = $param->email;
        $user->role = $param->role;
        $user->flag_active = true;
        $user->save();

        return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
    }

    public function toggleUser(Request $request)
    {
        $this->user = \Auth::user();
        $param = json_decode($request->getContent());

        $user = User::whereId($param->id)->first();
        if (!$user) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

        $user->flag_active = !$user->flag_active;
        $user->save();

        return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
    }

    public function updateUser(Request $request)
    {
        $this->user = \Auth::user();
        $param = json_decode($request->getContent());

        $user = User::whereId($param->id)->first();
        if (!$user) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

        $user->name = $param->name;
        $user->email = $param->email;
        $user->role = $param->role;
        $user->save();

        return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
    }

    public function removeUser(Request $request)
    {
        $this->user = \Auth::user();
        $param = json_decode($request->getContent());

        $user = User::whereId($param->id)->first();
        if (!$user) return response()->json(['message' => "Data tidak ditemukan"], HttpStatus::ERROR);

        try {
            $user->delete();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], HttpStatus::ERROR);
        }

        return response()->json(['message' => 'success'], HttpStatus::SUCCESS);
    }

}
