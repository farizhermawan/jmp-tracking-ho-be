<?php

namespace App\Http\Middleware;

use App\Enums\HttpStatus;
use App\User;
use Auth0\Login\Contract\Auth0UserRepository;
use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\InvalidTokenException;
use Cache;
use Closure;

class CheckJWT
{
    protected $userRepository;

    /**
     * CheckJWT constructor.
     *
     * @param Auth0UserRepository $userRepository
     */
    public function __construct(Auth0UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth0 = \App::make('auth0');
        $accessToken = $request->bearerToken();
        if ($accessToken == null) return response()->json(["message" => "Authentication is required"], HttpStatus::SUCCESS);
        try {
            $md5AccessToken = md5($accessToken);
            $tokenInfo = Cache::remember("decodeJWT|{$md5AccessToken}", 60, function() use ($accessToken, $auth0) {
                return $auth0->decodeJWT($accessToken);
            });

            $login = Cache::remember("token|{$tokenInfo->sub}", 60, function() use ($tokenInfo) {
                return $this->userRepository->getUserByDecodedJWT($tokenInfo);
            });

            if (!$login) {
                Cache::forget("token|{$tokenInfo->sub}");
                return response()->json(["message" => "Unauthorized user"], HttpStatus::SUCCESS);
            }

            $uid = $login->getAuthIdentifier();
            $user = User::whereAuth0Id($uid)->first();

            if (!$user) {
                return response()->json(["message" => "Unregistered user", "uid" => $uid], HttpStatus::SUCCESS);
            }

            \Auth::login($user, true);

        } catch (CoreException $e) {
            return response()->json(["message" => $e->getMessage()], HttpStatus::ERROR);
        } catch (InvalidTokenException $e) {
            return response()->json(["message" => $e->getMessage()], HttpStatus::ERROR);
        }
        return $next($request);
    }
}
