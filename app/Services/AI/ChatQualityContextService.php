<?php

namespace App\Services\AI;

use App\Models\Quality;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ChatQualityContextService
{
    public function __construct(
        private readonly UserDataScopeService $scopeService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function buildStats(User $user): ?array
    {
        if (!$this->scopeService->hasModulePermission($user, 'Quality Control', 'read')) {
            return null;
        }

        $baseQuery = $this->latestQualityPerAppointmentQuery($user);

        $byQualityStatus = (clone $baseQuery)
            ->select('qualities.status', DB::raw('count(*) as count'))
            ->groupBy('qualities.status')
            ->pluck('count', 'status')
            ->toArray();

        $byAuditStatus = (clone $baseQuery)
            ->select('qualities.auditstatus', DB::raw('count(*) as count'))
            ->groupBy('qualities.auditstatus')
            ->pluck('count', 'auditstatus')
            ->toArray();

        $byAppointmentStatus = (clone $baseQuery)
            ->join('appointments', 'qualities.appointment_id', '=', 'appointments.id')
            ->select('appointments.current_status', DB::raw('count(*) as count'))
            ->groupBy('appointments.current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        $qaApprovedQuery = (clone $baseQuery)->where(function (Builder $q) {
            $q->where('qualities.status', 'QA-Approved')
                ->orWhere('qualities.status', 'QA Approved')
                ->orWhereRaw("LOWER(REPLACE(qualities.status, ' ', '-')) = 'qa-approved'");
        });

        $qaApprovedRecords = (clone $qaApprovedQuery)
            ->with(['appointment.followupBusiness:id,name'])
            ->orderByDesc('qualities.updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Quality $quality) => [
                'appointment_id' => $quality->appointment_id,
                'quality_status' => $quality->status,
                'audit_status' => $quality->auditstatus,
                'appointment_status' => $quality->appointment?->current_status,
                'business_name' => $quality->appointment?->followupBusiness?->name,
            ])
            ->toArray();

        $qualifiedCount = (int) collect($byAuditStatus)->reduce(
            fn (int $carry, $count, string $status) => strtolower($status) === 'qualified' ? $carry + (int) $count : $carry,
            0
        );

        return [
            'total_latest_per_appointment' => (clone $baseQuery)->count('qualities.id'),
            'qa_approved_count' => (clone $qaApprovedQuery)->count('qualities.id'),
            'audit_qualified_count' => $qualifiedCount,
            'by_quality_status' => $byQualityStatus,
            'by_audit_status' => $byAuditStatus,
            'by_appointment_status' => $byAppointmentStatus,
            'qa_approved_appointments' => $qaApprovedRecords,
            'definitions' => [
                'qa_approved_count' => 'Quality records with status QA-Approved (latest per appointment).',
                'audit_qualified_count' => 'Quality records with auditstatus qualified (Completed tab in QC).',
                'conducted_appointments' => 'Appointment current_status Conducted — NOT the same as QA-Approved.',
            ],
        ];
    }

    private function latestQualityPerAppointmentQuery(User $user): Builder
    {
        $latestQualityIds = Quality::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('appointment_id')
            ->pluck('id');

        $query = Quality::query()->whereIn('qualities.id', $latestQualityIds);

        $scope = $this->scopeService->resolve($user);

        if (!$scope->isAdmin()) {
            $query->whereIn('qualities.assigned_user', $scope->allowedUserIds);
        }

        return $query;
    }
}
