<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Jwtverifie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->header('Authorization')) {
            // Jika header Authorization tidak ditemukan
            return response()->json([
                'status' => 2,
                'message' => 'Unauthorized: Missing Authorization Token',
            ], 401);
        }

        if (!$request->header('username')) {
            // Jika header username tidak ditemukan
            return response()->json([
                'status' => 2,
                'message' => 'Unauthorized: Missing Username',
            ], 401);
        }

        $header = $request->header('Authorization');
        $username = $request->header('username');

        if (Str::startsWith($header, 'Bearer ')) {
            $token = Str::substr($header, 7); // Ambil token setelah "Bearer "
            try {
                // Decode token JWT
                $decoded = JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));

                // Validasi username dengan token JWT
                $usertoken = $decoded->sub ?? null; // `sub` diisi saat membuat token
                $exptoken = $decoded->exp ?? null; // Waktu kedaluwarsa token

                if ($username !== $usertoken) {
                    return response()->json([
                        'status' => 2,
                        'message' => 'Unauthorized: Invalid Username',
                    ], 403);
                }

                if (time() > $exptoken) {
                    return response()->json([
                        'status' => 2,
                        'message' => 'Unauthorized: Token Expired',
                    ], 403);
                }

                // Token valid, lanjutkan request
                return $next($request);
            } catch (\Exception $e) {
                // Token tidak valid
                return response()->json([
                    'status' => 2,
                    'message' => 'Unauthorized: Invalid Token',
                    'error' => $e->getMessage(),
                ], 401);
            }
        }

        return response()->json([
            'status' => 2,
            'message' => 'Unauthorized: Bearer Token Missing',
        ], 401);
    }
}
