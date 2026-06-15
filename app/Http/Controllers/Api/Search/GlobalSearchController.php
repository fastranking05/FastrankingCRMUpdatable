<?php

namespace App\Http\Controllers\Api\Search;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class GlobalSearchController extends BaseApiController
{
    public function __construct(
        private readonly GlobalSearchService $globalSearchService,
    ) {}

    /**
     * Search across all indexed CRM records.
     *
     * GET /api/search?q=john&types[]=deal&types[]=business&page=1&limit=20
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:255',
            'types' => 'sometimes|array',
            'types.*' => 'string',
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid search parameters', 422, $validator->errors());
        }

        try {
            $data = $this->globalSearchService->search(
                $request->input('q'),
                [
                    'types' => $request->input('types', []),
                    'page' => $request->input('page', 1),
                    'limit' => $request->input('limit'),
                ]
            );

            return $this->successResponse($data, 'Search completed successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 503);
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Search failed',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Typesense connection and index status.
     *
     * GET /api/search/status
     */
    public function status(): JsonResponse
    {
        return $this->successResponse(
            $this->globalSearchService->status(),
            'Search status retrieved successfully'
        );
    }

    /**
     * Rebuild the global search index from database records.
     *
     * POST /api/search/reindex?fresh=1
     */
    public function reindex(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fresh' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid reindex parameters', 422, $validator->errors());
        }

        try {
            $counts = $this->globalSearchService->reindex(
                $request->boolean('fresh', false)
            );

            return $this->successResponse([
                'indexed_counts' => $counts,
            ], 'Global search index rebuilt successfully');
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 503);
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Reindex failed',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}
