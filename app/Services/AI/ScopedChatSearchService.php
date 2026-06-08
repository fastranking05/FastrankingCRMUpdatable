<?php

namespace App\Services\AI;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\AI\Data\UserDataScope;
use App\Services\Search\Index\GlobalSearchDocumentBuilder;
use App\Services\Search\SearchEntityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScopedChatSearchService
{
    public function __construct(
        private readonly UserDataScopeService $scopeService,
        private readonly GlobalSearchDocumentBuilder $documentBuilder,
    ) {}

    /**
     * @param  array<int, string>  $entityTypes
     * @return array<int, array<string, mixed>>
     */
    public function search(User $user, string $query, array $entityTypes, int $limit): array
    {
        $scope = $this->scopeService->resolve($user);
        $like = '%' . trim($query) . '%';
        $results = [];

        foreach ($entityTypes as $entityType) {
            $searcher = $this->searcherFor($entityType);

            if ($searcher === null) {
                continue;
            }

            $builder = $searcher($like);
            $builder = $this->applyScope($builder, $user, $scope, $entityType);
            $models = $builder->limit($limit)->get();

            foreach ($models as $model) {
                $document = $this->documentBuilder->buildFromModel($model);

                if ($document === null) {
                    continue;
                }

                $results[] = [
                    'entity_type' => $document['entity_type'],
                    'entity_type_label' => config('ai.entities.' . $document['entity_type'] . '.label')
                        ?? config('elasticsearch.entity_types.' . $document['entity_type'] . '.label'),
                    'entity_id' => $document['entity_id'],
                    'title' => $document['title'],
                    'subtitle' => $document['subtitle'],
                    'metadata' => $document['metadata'],
                    'updated_at' => $document['updated_at'],
                ];
            }
        }

        usort($results, fn (array $a, array $b) => strcmp($b['updated_at'], $a['updated_at']));

        return array_slice($results, 0, $limit);
    }

    public function countScoped(Builder $query, User $user, string $entityType): int
    {
        $scope = $this->scopeService->resolve($user);

        return $this->applyScope($query, $user, $scope, $entityType)->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(User $user, string $entityType, int $limit): array
    {
        $config = config('ai.entities.' . $entityType);
        $modelClass = config('elasticsearch.entity_types.' . $entityType . '.model');

        if (!$config || !$modelClass || !class_exists($modelClass)) {
            return [];
        }

        $scope = $this->scopeService->resolve($user);
        $query = $this->applyScope($modelClass::query(), $user, $scope, $entityType);

        $orderColumn = $entityType === SearchEntityType::BUSINESS ? 'created_at' : 'updated_at';

        return $query
            ->latest($orderColumn)
            ->limit($limit)
            ->get()
            ->map(function (Model $model) use ($entityType) {
                $document = $this->documentBuilder->buildFromModel($model);

                if ($document === null) {
                    return null;
                }

                return [
                    'entity_type' => $entityType,
                    'entity_type_label' => config('ai.entities.' . $entityType . '.label'),
                    'entity_id' => $document['entity_id'],
                    'title' => $document['title'],
                    'subtitle' => $document['subtitle'],
                    'metadata' => $document['metadata'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function applyScope(Builder $query, User $user, UserDataScope $scope, string $entityType): Builder
    {
        if ($scope->isAdmin()) {
            return $query;
        }

        $column = config('ai.entities.' . $entityType . '.scope_column', 'created_by');

        if ($column === 'id') {
            return $query->whereIn('id', $scope->allowedUserIds);
        }

        return $query->whereIn($column, $scope->allowedUserIds);
    }

    /**
     * @return (callable(string): Builder)|null
     */
    private function searcherFor(string $entityType): ?callable
    {
        return match ($entityType) {
            SearchEntityType::BUSINESS => fn (string $like) => FollowupBusiness::query()
                ->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->latest('updated_at'),
            SearchEntityType::CONTACT => fn (string $like) => FollowupAuthPerson::query()
                ->where(fn (Builder $q) => $q
                    ->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('primaryemail', 'like', $like)
                    ->orWhere('primarymobile', 'like', $like))
                ->latest('updated_at'),
            SearchEntityType::DEAL => fn (string $like) => Deal::query()
                ->with('followupBusiness')
                ->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('deal_stage', 'like', $like)
                    ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
                ->latest('updated_at'),
            SearchEntityType::APPOINTMENT => fn (string $like) => Appointment::query()
                ->with('followupBusiness')
                ->where(fn (Builder $q) => $q
                    ->where('status', 'like', $like)
                    ->orWhere('current_status', 'like', $like)
                    ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
                ->latest('updated_at'),
            SearchEntityType::EMAIL => fn (string $like) => Email::query()
                ->with('followupBusiness')
                ->where(fn (Builder $q) => $q
                    ->where('type', 'like', $like)
                    ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
                ->latest('updated_at'),
            SearchEntityType::CONSULTATION => fn (string $like) => Consultation::query()
                ->where(fn (Builder $q) => $q
                    ->where('status', 'like', $like)
                    ->orWhere('reason', 'like', $like))
                ->latest('updated_at'),
            SearchEntityType::SEO_AUDIT => fn (string $like) => SeoDetail::query()
                ->with('followupBusiness')
                ->where(fn (Builder $q) => $q
                    ->where('status', 'like', $like)
                    ->orWhere('auditor', 'like', $like)
                    ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
                ->latest('updated_at'),
            SearchEntityType::COMMENT => fn (string $like) => Comment::query()
                ->with('followupBusiness')
                ->where(fn (Builder $q) => $q
                    ->where('comment', 'like', $like)
                    ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
                ->latest('updated_at'),
            SearchEntityType::USER => fn (string $like) => User::query()
                ->where(fn (Builder $q) => $q
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->latest('updated_at'),
            default => null,
        };
    }
}
