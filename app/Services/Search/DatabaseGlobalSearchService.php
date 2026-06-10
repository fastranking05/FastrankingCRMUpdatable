<?php

namespace App\Services\Search;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\Search\Index\GlobalSearchDocumentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DatabaseGlobalSearchService
{
    public function __construct(
        private readonly GlobalSearchDocumentBuilder $documentBuilder,
    ) {}

    public function search(string $query, array $options = []): array
    {
        $trimmedQuery = trim($query);
        $limit = min(
            (int) ($options['limit'] ?? config('elasticsearch.search.default_limit', 20)),
            (int) config('elasticsearch.search.max_limit', 100)
        );
        $page = max(1, (int) ($options['page'] ?? 1));
        $types = $this->normalizeTypes($options['types'] ?? []);
        $like = '%' . $trimmedQuery . '%';

        $results = [];

        foreach ($this->searchableTypes($types) as $entityType => $searcher) {
            $models = $searcher($like);

            foreach ($models as $model) {
                $document = $this->documentBuilder->buildFromModel($model);

                if ($document === null) {
                    continue;
                }

                $results[] = [
                    'entity_type' => $document['entity_type'],
                    'entity_type_label' => config('elasticsearch.entity_types.' . $document['entity_type'] . '.label'),
                    'entity_id' => $document['entity_id'],
                    'title' => $document['title'],
                    'subtitle' => $document['subtitle'],
                    'route' => $document['route'],
                    'metadata' => $document['metadata'],
                    'score' => null,
                    'highlight' => [],
                    '_sort_at' => $document['updated_at'],
                ];
            }
        }

        usort($results, fn (array $a, array $b) => strcmp($b['_sort_at'], $a['_sort_at']));

        $total = count($results);
        $from = ($page - 1) * $limit;
        $paged = array_slice($results, $from, $limit);

        $paged = array_map(function (array $result) {
            unset($result['_sort_at']);

            return $result;
        }, $paged);

        $grouped = [];
        foreach ($paged as $result) {
            $type = $result['entity_type'] ?? 'unknown';
            $grouped[$type] = ($grouped[$type] ?? 0) + 1;
        }

        return [
            'query' => $trimmedQuery,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            'counts_by_type' => $grouped,
            'available_types' => $this->availableTypes(),
            'search_engine' => 'database',
            'results' => $paged,
        ];
    }

    private function searchableTypes(array $types): array
    {
        $all = [
            SearchEntityType::BUSINESS => fn (string $like) => $this->searchBusinesses($like),
            SearchEntityType::CONTACT => fn (string $like) => $this->searchContacts($like),
            SearchEntityType::DEAL => fn (string $like) => $this->searchDeals($like),
            SearchEntityType::APPOINTMENT => fn (string $like) => $this->searchAppointments($like),
            SearchEntityType::USER => fn (string $like) => $this->searchUsers($like),
            SearchEntityType::EMAIL => fn (string $like) => $this->searchEmails($like),
            SearchEntityType::CONSULTATION => fn (string $like) => $this->searchConsultations($like),
            SearchEntityType::SEO_AUDIT => fn (string $like) => $this->searchSeoAudits($like),
            SearchEntityType::COMMENT => fn (string $like) => $this->searchComments($like),
        ];

        if ($types === []) {
            return $all;
        }

        return array_intersect_key($all, array_flip($types));
    }

    private function searchBusinesses(string $like)
    {
        return FollowupBusiness::query()
            ->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('type', 'like', $like)
                ->orWhere('source_name', 'like', $like)
                ->orWhere('sub_source', 'like', $like)
                ->orWhere('website', 'like', $like))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchContacts(string $like)
    {
        return FollowupAuthPerson::query()
            ->where(fn (Builder $q) => $q
                ->where('firstname', 'like', $like)
                ->orWhere('middlename', 'like', $like)
                ->orWhere('lastname', 'like', $like)
                ->orWhere('job_title', 'like', $like)
                ->orWhere('seniority_level', 'like', $like)
                ->orWhere('linkedin_profile', 'like', $like)
                ->orWhere('primaryphone', 'like', $like)
                ->orWhere('primarymobile', 'like', $like)
                ->orWhere('primaryemail', 'like', $like)
                ->orWhere('altemail', 'like', $like))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchDeals(string $like)
    {
        return Deal::query()
            ->with('followupBusiness')
            ->where(fn (Builder $q) => $q
                ->where('id', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('type', 'like', $like)
                ->orWhere('deal_stage', 'like', $like)
                ->orWhere('selected_service', 'like', $like)
                ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchAppointments(string $like)
    {
        return Appointment::query()
            ->with('followupBusiness')
            ->where(fn (Builder $q) => $q
                ->where('id', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhere('current_status', 'like', $like)
                ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchUsers(string $like)
    {
        return User::query()
            ->where(fn (Builder $q) => $q
                ->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('username', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('mobile', 'like', $like)
                ->orWhere('emp_id', 'like', $like)
                ->orWhere('designation', 'like', $like))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchEmails(string $like)
    {
        return Email::query()
            ->with('followupBusiness')
            ->where(fn (Builder $q) => $q
                ->where('type', 'like', $like)
                ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchConsultations(string $like)
    {
        return Consultation::query()
            ->where(fn (Builder $q) => $q
                ->where('appointment_id', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhere('custom_status', 'like', $like)
                ->orWhere('reason', 'like', $like)
                ->orWhere('closer', 'like', $like))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchSeoAudits(string $like)
    {
        return SeoDetail::query()
            ->with('followupBusiness')
            ->where(fn (Builder $q) => $q
                ->where('status', 'like', $like)
                ->orWhere('reason', 'like', $like)
                ->orWhere('auditor', 'like', $like)
                ->orWhere('audited_website', 'like', $like)
                ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function searchComments(string $like)
    {
        return Comment::query()
            ->with('followupBusiness')
            ->where(fn (Builder $q) => $q
                ->where('comment', 'like', $like)
                ->orWhere('old_status', 'like', $like)
                ->orWhere('new_status', 'like', $like)
                ->orWhereHas('followupBusiness', fn (Builder $bq) => $bq->where('name', 'like', $like)))
            ->latest('updated_at')
            ->limit(50)
            ->get();
    }

    private function availableTypes(): array
    {
        return collect(config('elasticsearch.entity_types', []))
            ->map(fn (array $config, string $key) => [
                'key' => $key,
                'label' => $config['label'] ?? $key,
            ])
            ->values()
            ->all();
    }

    private function normalizeTypes(array $types): array
    {
        $allowed = SearchEntityType::all();

        return array_values(array_filter(
            array_map('strval', $types),
            fn (string $type) => in_array($type, $allowed, true)
        ));
    }
}
