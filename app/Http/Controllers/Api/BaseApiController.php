<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class BaseApiController extends Controller
{
    /**
     * Execute database transaction with try-catch and logging
     *
     * @param callable $callback
     * @param string $action
     * @param array $context
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeTransaction(callable $callback, string $action, array $context = [])
    {
        try {
            return DB::transaction(function () use ($callback, $action, $context) {
                $result = $callback();

                Log::info($action . ' successful', array_merge($context, [
                    'user_id' => auth()->id()
                ]));

                return $result;
            });
        } catch (Exception $e) {
            Log::error($action . ' failed', array_merge($context, [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Return success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = 'Success', int $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
