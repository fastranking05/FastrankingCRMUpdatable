<?php

namespace App\Services\AI;

use App\Models\Appointment;
use App\Models\Deal;
use App\Models\FollowupBusiness;
use App\Models\SeoDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChatQueryContextService
{
    public function __construct(
        private readonly UserDataScopeService $scopeService,
    ) {}

    /**
     * Build targeted CRM facts for analytics-style questions.
     *
     * @return array<string, mixed>
     */
    public function build(User $user, string $message): array
    {
        $normalized = strtolower(trim($message));
        $context = [];

        if ($this->mentionsLatestLead($normalized)) {
            $context['latest_lead'] = $this->latestLead($user);
        }

        if ($this->mentionsLeadsToday($normalized)) {
            $context['leads_created_today'] = $this->leadsCreatedToday($user);
        }

        if ($this->mentionsAppointmentsToday($normalized)) {
            $context['appointments_today'] = $this->appointmentsToday($user);
        }

        if ($this->mentionsDealsToday($normalized)) {
            $context['deals_created_today'] = $this->dealsCreatedToday($user);
        }

        if ($this->mentionsSeoAudits($normalized)) {
            $context['seo_audits'] = $this->seoAuditStats($user);
        }

        return $context;
    }

    public function isAnalyticsQuestion(string $message): bool
    {
        $normalized = strtolower(trim($message));

        return $this->mentionsLatestLead($normalized)
            || $this->mentionsLeadsToday($normalized)
            || $this->mentionsAppointmentsToday($normalized)
            || $this->mentionsDealsToday($normalized)
            || $this->mentionsSeoAudits($normalized)
            || (bool) preg_match('/\b(how many|how much|count|total|number of)\b/', $normalized);
    }

    public function isGreeting(string $message): bool
    {
        $normalized = strtolower(trim($message));

        return (bool) preg_match('/^(hi|hello|hey|namaste|good\s+(morning|afternoon|evening))[\s!.,?]*$/i', $normalized);
    }

    private function mentionsLatestLead(string $message): bool
    {
        return (bool) preg_match('/\b(latest|last|newest|recent)\b/', $message)
            && (bool) preg_match('/\b(lead|leads|business|businesses)\b/', $message);
    }

    private function mentionsLeadsToday(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(lead|leads|business|businesses)\b/', $message);
    }

    private function mentionsAppointmentsToday(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(appointment|appointments|meeting|meetings)\b/', $message);
    }

    private function mentionsDealsToday(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(deal|deals)\b/', $message);
    }

    private function mentionsSeoAudits(string $message): bool
    {
        return (bool) preg_match('/\b(seo)\b/', $message)
            || ((bool) preg_match('/\b(audit|audits)\b/', $message) && (bool) preg_match('/\b(pending|seo)\b/', $message));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestLead(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'Leads', 'read')) {
            return null;
        }

        $lead = $this->scopeService
            ->scopeQuery(FollowupBusiness::query(), $user)
            ->select('id', 'name', 'category', 'type', 'phone', 'email', 'website', 'created_by', 'created_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$lead) {
            return null;
        }

        return [
            'id' => $lead->id,
            'business_name' => $lead->name,
            'category' => $lead->category,
            'type' => $lead->type,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'website' => $lead->website,
            'created_by' => $lead->created_by,
            'created_at' => $lead->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function leadsCreatedToday(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'Leads', 'read')) {
            return null;
        }

        $query = $this->scopeService
            ->scopeQuery(FollowupBusiness::query(), $user)
            ->whereDate('created_at', Carbon::today());

        $records = (clone $query)
            ->select('id', 'name', 'category', 'created_by', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (FollowupBusiness $lead) => [
                'id' => $lead->id,
                'business_name' => $lead->name,
                'category' => $lead->category,
                'created_by' => $lead->created_by,
                'created_at' => $lead->created_at?->toDateTimeString(),
            ])
            ->toArray();

        return [
            'count' => $query->count(),
            'date' => Carbon::today()->toDateString(),
            'records' => $records,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function appointmentsToday(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'Appointment', 'read')) {
            return null;
        }

        $query = $this->scopeService
            ->scopeQuery(Appointment::query(), $user)
            ->with(['followupBusiness:id,name'])
            ->whereDate('date', Carbon::today());

        return [
            'count' => $query->count(),
            'date' => Carbon::today()->toDateString(),
            'records' => $query
                ->select('id', 'date', 'status', 'current_status', 'followup_business_id', 'created_by')
                ->orderBy('date')
                ->limit(10)
                ->get()
                ->map(fn (Appointment $apt) => [
                    'id' => $apt->id,
                    'date' => $apt->date?->toDateString(),
                    'status' => $apt->status,
                    'current_status' => $apt->current_status,
                    'business_name' => $apt->followupBusiness?->name,
                    'created_by' => $apt->created_by,
                ])
                ->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function dealsCreatedToday(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'Deals', 'read')) {
            return null;
        }

        $query = $this->scopeService
            ->scopeQuery(Deal::query(), $user)
            ->whereDate('created_at', Carbon::today());

        return [
            'count' => $query->count(),
            'date' => Carbon::today()->toDateString(),
            'records' => $query
                ->with(['followupBusiness:id,name'])
                ->select('id', 'name', 'deal_stage', 'created_by', 'created_at', 'followup_business_id')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn (Deal $deal) => [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'deal_stage' => $deal->deal_stage,
                    'business_name' => $deal->followupBusiness?->name,
                    'created_by' => $deal->created_by,
                    'created_at' => $deal->created_at?->toDateTimeString(),
                ])
                ->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function seoAuditStats(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'SEO', 'read')) {
            return null;
        }

        $scope = $this->scopeService->resolve($user);
        $query = SeoDetail::query()->with(['followupBusiness:id,name']);

        if (!$scope->isAdmin()) {
            $query->whereIn('assigned_user', $scope->allowedUserIds);
        }

        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $pendingRecords = (clone $query)
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (SeoDetail $seo) => [
                'id' => $seo->id,
                'status' => $seo->status,
                'business_name' => $seo->followupBusiness?->name,
                'auditor' => $seo->auditor,
                'assigned_user' => $seo->assigned_user,
                'created_at' => $seo->created_at?->toDateTimeString(),
            ])
            ->toArray();

        return [
            'total_count' => $query->count(),
            'pending_count' => (int) ($byStatus['Pending'] ?? 0),
            'by_status' => $byStatus,
            'pending_records' => $pendingRecords,
        ];
    }
}
