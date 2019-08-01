<?php

namespace App\Http\Controllers\API;

use App\Enums\HttpStatus;
use App\Http\Controllers\Controller;
use App\User;
use Cache;
use Illuminate\Http\Request;

/**
 * AuthController
 *
 * @property \App\User $user
 */
class AuthController extends Controller
{
  public function callback(Request $request)
  {
    $param = json_decode($request->getContent());
    $idTokenPayload = $param->idTokenPayload;
    $user = User::whereAuth0Id($idTokenPayload->sub)->first();
    if ($user == null) {
      $user = User::whereEmail($idTokenPayload->email)->first();
      if ($user == null) return response()->json(['message' => "User not found"], HttpStatus::SUCCESS);
      $user->auth0_id = $idTokenPayload->sub;
      $user->name = $idTokenPayload->name;
      $user->email = $idTokenPayload->email;
      $user->save();
    }
    Cache::remember("callback|{$idTokenPayload->at_hash}", 60, function() use ($param) {
      return $param;
    });
    return response()->json(['message' => "success", "user" => $user], HttpStatus::SUCCESS);
  }

  public function getProfile(Request $request)
  {
    $this->user = \Auth::user();
    if (!$this->user->flag_active) return response()->json(['message' => "inactive", "profile" => $this->user], HttpStatus::SUCCESS);
    return response()->json(['message' => "success", "profile" => $this->user], HttpStatus::SUCCESS);
  }

  public function savedAuth(Request $request)
  {
    $param = json_decode($request->getContent());
    $savedAuth = Cache::get("callback|{$param->hash}", null);
    return response()->json(['message' => "success", "authResult" => $savedAuth], HttpStatus::SUCCESS);
  }
}
