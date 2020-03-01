<?php

namespace App\Http\Middleware;

class AuthenticateWithBasicAuth
{
  /**
   * Handle the incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return void
   */
  public function handle($request, $next)
  {
    if ($request->getUser() == null) {
      return abort(401, 'Unauthorized', ['WWW-Authenticate' => 'Basic']);
    } else if ($request->getUser() == env('API_USERNAME') && $request->getPassword() == env('API_PASSWORD')) {
      return $next($request);
    }
    return abort(401, 'Unauthorized', ['WWW-Authenticate' => 'Basic']);
  }
}
