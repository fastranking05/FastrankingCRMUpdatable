<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupDetailController extends BaseApiController
{
    /**
     * Display a listing of follow-up details.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupDetail::with(['creator:id,first_name,last_name', 'business:id,name']);

            // Filter by business_id
            if ($request->has('business_id')) {
                $query->where('followup_business_id', $request->business_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by source
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $details = $query->orderBy('date', 'desc')->orderBy('time', 'desc')->paginate($perPage);

            return $this->successResponse($details, 'Follow-up details retrieved successfully');
        }, 'Follow-up detail list retrieval');
    }

    /**
     * Store a newly created follow-up detail.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'required|exists:followup_businesses,id',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->all();
            $data['created_by'] = auth()->id();

            $detail = FollowupDetail::create($data);

            $detail->load(['creator:id,first_name,last_name', 'business:id,name']);

            return $this->successResponse($detail, 'Follow-up detail created successfully', 201);
        }, 'Follow-up detail creation', ['business_id' => $request->followup_business_id]);
    }

    /**
     * Display the specified follow-up detail.
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $detail = FollowupDetail::with(['creator:id,first_name,last_name', 'business:id,name'])->find($id);

            if (!$detail) {
                return $this->errorResponse('Follow-up detail not found', 404);
            }

            return $this->successResponse($detail, 'Follow-up detail retrieved successfully');
        }, 'Follow-up detail retrieval', ['detail_id' => $id]);
    }

    /**
     * Update the specified follow-up detail.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $detail = FollowupDetail::find($id);

        if (!$detail) {
            return $this->errorResponse('Follow-up detail not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'sometimes|required|exists:followup_businesses,id',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $detail) {
            $detail->update($request->all());

            $detail->load(['creator:id,first_name,last_name', 'business:id,name']);

            return $this->successResponse($detail, 'Follow-up detail updated successfully');
        }, 'Follow-up detail update', ['detail_id' => $detail->id]);
    }

    /**
     * Remove the specified follow-up detail.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $detail = FollowupDetail::find($id);

            if (!$detail) {
                return $this->errorResponse('Follow-up detail not found', 404);
            }

            // Comments will be deleted automatically due to cascade delete
            $detail->delete();

            return $this->successResponse(null, 'Follow-up detail deleted successfully');
        }, 'Follow-up detail deletion', ['detail_id' => $id]);
    }
}
