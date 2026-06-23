<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ZoomAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoomAccountController extends BaseApiController
{
    /**
     * Display a listing of zoom accounts.
     */
    public function index(): JsonResponse
    {
        $accounts = ZoomAccount::orderBy('account_name', 'asc')->get();

        return $this->successResponse($accounts, 'Zoom accounts retrieved successfully');
    }

    /**
     * Store a newly created zoom account.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_id' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string',
            'secret_token' => 'required|string',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $account = ZoomAccount::create($validator->validated());

        return $this->successResponse($account, 'Zoom account created successfully', 201);
    }

    /**
     * Display the specified zoom account.
     */
    public function show(string $id): JsonResponse
    {
        $account = ZoomAccount::findOrFail($id);

        return $this->successResponse($account, 'Zoom account retrieved successfully');
    }

    /**
     * Update the specified zoom account.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $account = ZoomAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_id' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'nullable|string',
            'secret_token' => 'nullable|string',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $data = [
            'username' => $request->username,
            'account_name' => $request->account_name,
            'account_id' => $request->account_id,
            'client_id' => $request->client_id,
            'email' => $request->email,
        ];

        if ($request->filled('client_secret')) {
            $data['client_secret'] = $request->client_secret;
        }

        if ($request->filled('secret_token')) {
            $data['secret_token'] = $request->secret_token;
        }

        $account->update($data);

        return $this->successResponse($account, 'Zoom account updated successfully');
    }

    /**
     * Remove the specified zoom account.
     */
    public function destroy(string $id): JsonResponse
    {
        $account = ZoomAccount::findOrFail($id);
        $account->delete();

        return $this->successResponse(null, 'Zoom account deleted successfully');
    }
}
