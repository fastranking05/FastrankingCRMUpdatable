<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatSecurityMiddleware
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('ai.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'AI chat is disabled. Set AI_CHAT_ENABLED=true in .env',
            ], 503);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if (strtolower((string) $user->status) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active. Chat access is denied.',
            ], 403);
        }

        return $next($request);
    }
}
