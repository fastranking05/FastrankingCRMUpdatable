<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupCommentController extends BaseApiController
{
    /**
     * Display a listing of follow-up comments.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupComment::with(['creator:id,first_name,last_name', 'followupDetail:id,source,status']);

            // Filter by followup_detail_id
            if ($request->has('followup_detail_id')) {
                $query->where('followup_detail_id', $request->followup_detail_id);
            }

            // Filter by comment_type
            if ($request->has('comment_type')) {
                $query->where('comment_type', $request->comment_type);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $comments = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($comments, 'Follow-up comments retrieved successfully');
        }, 'Follow-up comment list retrieval');
    }

    /**
     * Store a newly created follow-up comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_detail_id' => 'required|exists:followup_details,id',
            'comment' => 'required|string',
            'comment_type' => 'nullable|in:note,call,email,meeting,other',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->all();
            $data['comment_type'] = $data['comment_type'] ?? 'note';
            $data['created_by'] = auth()->id();

            $comment = FollowupComment::create($data);

            $comment->load(['creator:id,first_name,last_name', 'followupDetail:id,source,status']);

            return $this->successResponse($comment, 'Follow-up comment created successfully', 201);
        }, 'Follow-up comment creation', ['followup_detail_id' => $request->followup_detail_id]);
    }

    /**
     * Display the specified follow-up comment.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $comment = FollowupComment::with(['creator:id,first_name,last_name', 'followupDetail:id,source,status'])->find($id);

            if (!$comment) {
                return $this->errorResponse('Follow-up comment not found', 404);
            }

            return $this->successResponse($comment, 'Follow-up comment retrieved successfully');
        }, 'Follow-up comment retrieval', ['comment_id' => $id]);
    }

    /**
     * Update the specified follow-up comment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $comment = FollowupComment::find($id);

        if (!$comment) {
            return $this->errorResponse('Follow-up comment not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'sometimes|required|string',
            'comment_type' => 'nullable|in:note,call,email,meeting,other',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $comment) {
            $comment->update($request->all());

            $comment->load(['creator:id,first_name,last_name', 'followupDetail:id,source,status']);

            return $this->successResponse($comment, 'Follow-up comment updated successfully');
        }, 'Follow-up comment update', ['comment_id' => $comment->id]);
    }

    /**
     * Remove the specified follow-up comment.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $comment = FollowupComment::find($id);

            if (!$comment) {
                return $this->errorResponse('Follow-up comment not found', 404);
            }

            $comment->delete();

            return $this->successResponse(null, 'Follow-up comment deleted successfully');
        }, 'Follow-up comment deletion', ['comment_id' => $id]);
    }
}
