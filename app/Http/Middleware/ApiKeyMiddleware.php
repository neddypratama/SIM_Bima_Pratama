<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->query('api_key');
        $validKey = config('app.api_key', env('API_KEY', 'bima-pratama-secret-key'));

        if (!$apiKey || $apiKey !== $validKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing API Key.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
