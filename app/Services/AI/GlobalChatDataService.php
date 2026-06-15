<?php

namespace App\Services\AI;

use App\Models\FollowupBusiness;
use App\Models\Quality;
use App\Models\SeoDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GlobalChatDataService
{
    public function __construct(
        private readonly UserDataScopeService $scopeService,
        private readonly ScopedChatSearchService $searchService,
        private readonly ChatQueryContextService $queryContext,
    ) {}

    /**
     * Fetch all CRM data the logged-in user can access (single global source).
     *
     * @return array<string, mixed>
     */
    public function fetch(User $user, string $message): array
    {
        $scope = $this->scopeService->resolve($user);
        $readableEntities = $this->readableEntityTypes($user);
        $modulePermissions = $this->modulePermissions($user);

        $searchLimit = (int) config('ai.chat.search_result_limit', 10);
        $recentLimit = (int) config('ai.chat.recent_per_entity_limit', 3);

        $payload = [
            'access' => [
                'user' => [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'user_type' => $user->user_type,
                ],
                'scope' => $scope->toArray(),
                'readable_modules' => array_values(array_unique(array_column($modulePermissions, 'module'))),
                'module_permissions' => $modulePermissions,
            ],
            'summaries' => $this->summaries($user, $readableEntities),
            'query_context' => $this->queryContext->build($user, $message),
            'recent_records' => $this->recentRecords($user, $readableEntities, $recentLimit),
        ];

        if (!$this->queryContext->isGreeting($message) && !$this->queryContext->isAnalyticsQuestion($message)) {
            $searchTerm = $this->extractSearchTerm($message);

            if ($searchTerm !== null) {
                $payload['search'] = [
                    'query' => $searchTerm,
                    'results' => $this->searchService->search($user, $searchTerm, $readableEntities, $searchLimit),
                ];
            }
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    private function readableEntityTypes(User $user): array
    {
        $types = [];

        foreach (config('ai.entities', []) as $entityType => $config) {
            if ($this->scopeService->hasModulePermission($user, $config['module'], 'read')) {
                $types[] = $entityType;
            }
        }

        return $types;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modulePermissions(User $user): array
    {
        $userRoleIds = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.status', 'active')
            ->pluck('roles.id')
            ->toArray();

        if ($userRoleIds === []) {
            return [];
        }

        return DB::table('module_role')
            ->join('modules', 'module_role.module_id', '=', 'modules.id')
            ->whereIn('module_role.role_id', $userRoleIds)
            ->where('modules.status', 'active')
            ->where('module_role.can_read', true)
            ->select('modules.name as module')
            ->distinct()
            ->get()
            ->map(fn ($row) => ['module' => $row->module, 'can_read' => true])
            ->toArray();
    }

    /**
     * @param  array<int, string>  $readableEntities
     * @return array<string, int|array<string, int>>
     */
    private function summaries(User $user, array $readableEntities): array
    {
        $summaries = [];

        foreach ($readableEntities as $entityType) {
            $modelClass = config('global_search.entity_types.' . $entityType . '.model');

            if (!$modelClass || !class_exists($modelClass)) {
                continue;
            }

            $label = config('ai.entities.' . $entityType . '.label', $entityType);
            $summaries[$label] = $this->searchService->countScoped($modelClass::query(), $user, $entityType);
        }

        if ($this->scopeService->hasModulePermission($user, 'Follow-Up', 'read')) {
            $summaries['Follow-Ups (with details)'] = $this->searchService->countScoped(
                FollowupBusiness::query()->whereHas('followupDetails'),
                $user,
                'business'
            );
        }

        if ($this->scopeService->hasModulePermission($user, 'Quality Control', 'read')) {
            $scope = $this->scopeService->resolve($user);
            $qualityQuery = Quality::query();

            if (!$scope->isAdmin()) {
                $qualityQuery->whereIn('assigned_user', $scope->allowedUserIds);
            }

            $summaries['Quality Audits'] = $qualityQuery->count();
        }

        if ($this->scopeService->hasModulePermission($user, 'SEO', 'read')) {
            $scope = $this->scopeService->resolve($user);
            $seoQuery = SeoDetail::query();

            if (!$scope->isAdmin()) {
                $seoQuery->whereIn('assigned_user', $scope->allowedUserIds);
            }

            $summaries['SEO Audits (total)'] = $seoQuery->count();
            $summaries['SEO Audits (pending)'] = (clone $seoQuery)->where('status', 'Pending')->count();
        }

        return $summaries;
    }

    /**
     * @param  array<int, string>  $readableEntities
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function recentRecords(User $user, array $readableEntities, int $limit): array
    {
        $recent = [];

        foreach ($readableEntities as $entityType) {
            $label = config('ai.entities.' . $entityType . '.label', $entityType);
            $records = $this->searchService->recent($user, $entityType, $limit);

            if ($records !== []) {
                $recent[$label] = $records;
            }
        }

        return $recent;
    }

    private function extractSearchTerm(string $message): ?string
    {
        $stopWords = [
            'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare',
            'ought', 'used', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by',
            'from', 'up', 'about', 'into', 'through', 'during', 'before', 'after',
            'above', 'below', 'between', 'out', 'off', 'over', 'under', 'again',
            'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why',
            'how', 'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such',
            'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very',
            'and', 'but', 'or', 'because', 'as', 'until', 'while', 'what', 'which',
            'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'i', 'me', 'my',
            'mine', 'myself', 'we', 'our', 'ours', 'you', 'your', 'yours', 'he',
            'him', 'his', 'she', 'her', 'hers', 'it', 'its', 'they', 'them', 'their',
            'show', 'tell', 'give', 'get', 'find', 'search', 'list', 'kitne', 'kya',
            'kaise', 'kab', 'kahan', 'kaun', 'mere', 'mera', 'meri', 'mujhe', 'mujhko',
            'aaj', 'kal', 'today', 'tomorrow', 'yesterday', 'many', 'much', 'please',
            'batao', 'dikhao', 'hai', 'hain', 'ho', 'ke', 'ki', 'ka', 'ko', 'se', 'par',
            'aur', 'ya', 'main', 'hum', 'sab', 'sabhi', 'data', 'details', 'detail',
            'information', 'info', 'about', 'regarding', 'related', 'crm', 'record',
            'records', 'status', 'count', 'total', 'number', 'hi', 'hello', 'hey',
            'namaste', 'latest', 'last', 'newest', 'recent', 'entered', 'entred',
            'created', 'pending', 'business', 'name', 'lead', 'leads', 'deal', 'deals',
            'appointment', 'appointments', 'audit', 'audits', 'seo', 'many', 'much',
        ];

        $normalized = mb_strtolower(trim($message));
        $words = preg_split('/[\s,.?!;:]+/u', $normalized) ?: [];
        $meaningful = [];

        foreach ($words as $word) {
            $word = trim($word);

            if (mb_strlen($word) < 3 || in_array($word, $stopWords, true)) {
                continue;
            }

            $meaningful[] = $word;
        }

        if ($meaningful === []) {
            return null;
        }

        $term = implode(' ', array_slice($meaningful, 0, 4));

        return mb_strlen($term) >= 3 ? $term : null;
    }
}
