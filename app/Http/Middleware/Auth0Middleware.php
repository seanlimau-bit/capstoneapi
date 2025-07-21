<?php

namespace App\Http\Middleware;

use Closure;
use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\Utility\JWTVerifier;

class Auth0JWTMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $verifier = new JWTVerifier([
            'supported_algs' => ['RS256'],
            'valid_audiences' => ['YOUR_API_IDENTIFIER'],
            'authorized_iss' => ['https://YOUR_AUTH0_DOMAIN/']
        ]);

        try {
            $decoded = $verifier->verifyAndDecode($token);
        } catch (InvalidTokenException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Optionally set the user info in the request
        $request->auth0_user = $decoded;

        return $next($request);
    }
}
