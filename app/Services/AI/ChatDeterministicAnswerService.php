<?php

namespace App\Services\AI;

class ChatDeterministicAnswerService
{
    /**
     * Return a factual answer from query_context when the question maps to exact counts.
     * Avoids LLM hallucination for analytics-style questions.
     */
    public function tryResolve(string $message, array $crmData): ?string
    {
        $context = $crmData['query_context'] ?? [];
        $normalized = strtolower(trim($message));

        if ($quality = $context['quality_audits'] ?? null) {
            if ($this->asksQaApprovedCount($normalized)) {
                return $this->formatQaApprovedAnswer($quality);
            }

            if ($this->asksAuditQualifiedCount($normalized)) {
                return $this->formatAuditQualifiedAnswer($quality);
            }

            if ($this->asksQualityTotalCount($normalized)) {
                return $this->formatQualityTotalAnswer($quality);
            }
        }

        if ($seo = $context['seo_audits'] ?? null) {
            if ($this->asksSeoPendingCount($normalized)) {
                return $this->formatSeoPendingAnswer($seo);
            }
        }

        if ($leads = $context['leads_created_today'] ?? null) {
            if ($this->asksLeadsTodayCount($normalized)) {
                return $this->formatCountAnswer('leads created today', (int) $leads['count'], $leads['date'] ?? null);
            }
        }

        if ($appointments = $context['appointments_today'] ?? null) {
            if ($this->asksAppointmentsTodayCount($normalized)) {
                return $this->formatCountAnswer('appointments today', (int) $appointments['count'], $appointments['date'] ?? null);
            }
        }

        if ($deals = $context['deals_created_today'] ?? null) {
            if ($this->asksDealsTodayCount($normalized)) {
                return $this->formatCountAnswer('deals created today', (int) $deals['count'], $deals['date'] ?? null);
            }
        }

        return null;
    }

    private function asksQaApprovedCount(string $message): bool
    {
        $mentionsApproved = (bool) preg_match('/\b(approved|approval|approve)\b/', $message);
        $mentionsQuality = (bool) preg_match('/\b(quality|qa)\b/', $message)
            || ((bool) preg_match('/\b(approved|approval)\b/', $message) && (bool) preg_match('/\b(appointment|appointments)\b/', $message));

        return $mentionsApproved && $mentionsQuality;
    }

    private function asksAuditQualifiedCount(string $message): bool
    {
        return (bool) preg_match('/\b(completed|qualified)\b/', $message)
            && (bool) preg_match('/\b(quality|qa|audit)\b/', $message)
            && (bool) preg_match('/\b(how many|count|total|number of)\b/', $message);
    }

    private function asksQualityTotalCount(string $message): bool
    {
        return (bool) preg_match('/\b(how many|count|total|number of)\b/', $message)
            && (bool) preg_match('/\b(quality|qa)\b/', $message)
            && !(bool) preg_match('/\b(approved|approval|pending|qualified|completed)\b/', $message);
    }

    private function asksSeoPendingCount(string $message): bool
    {
        return (bool) preg_match('/\b(pending)\b/', $message)
            && (bool) preg_match('/\b(seo|audit)\b/', $message)
            && (bool) preg_match('/\b(how many|count|total|number of)\b/', $message);
    }

    private function asksLeadsTodayCount(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(lead|leads|business)\b/', $message)
            && (bool) preg_match('/\b(how many|count|total|kitne|number of)\b/', $message);
    }

    private function asksAppointmentsTodayCount(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(appointment|appointments)\b/', $message)
            && (bool) preg_match('/\b(how many|count|total|kitne|number of)\b/', $message)
            && !(bool) preg_match('/\b(approved|quality|qa)\b/', $message);
    }

    private function asksDealsTodayCount(string $message): bool
    {
        return (bool) preg_match('/\b(today)\b/', $message)
            && (bool) preg_match('/\b(deal|deals)\b/', $message)
            && (bool) preg_match('/\b(how many|count|total|kitne|number of)\b/', $message);
    }

    /**
     * @param  array<string, mixed>  $quality
     */
    private function formatQaApprovedAnswer(array $quality): string
    {
        $count = (int) ($quality['qa_approved_count'] ?? 0);
        $records = $quality['qa_approved_appointments'] ?? [];

        $lines = [
            "There are {$count} appointment(s) with QA-Approved status in Quality Control (latest record per appointment).",
            'Note: QA-Approved is different from Conducted or audit qualified — only QA-Approved status is counted here.',
        ];

        if ($count > 0 && $records !== []) {
            $lines[] = 'QA-Approved appointments:';
            foreach (array_slice($records, 0, 10) as $record) {
                $id = $record['appointment_id'] ?? 'N/A';
                $business = $record['business_name'] ?? 'Unknown business';
                $lines[] = "- {$id} — {$business}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $quality
     */
    private function formatAuditQualifiedAnswer(array $quality): string
    {
        $count = (int) ($quality['audit_qualified_count'] ?? 0);

        return "There are {$count} quality audit(s) with qualified audit status (Completed tab in Quality Control). "
            . 'This is not the same as QA-Approved appointment status.';
    }

    /**
     * @param  array<string, mixed>  $quality
     */
    private function formatQualityTotalAnswer(array $quality): string
    {
        $count = (int) ($quality['total_latest_per_appointment'] ?? 0);
        $byStatus = $quality['by_quality_status'] ?? [];

        $breakdown = $byStatus === []
            ? ''
            : ' Breakdown by quality status: ' . collect($byStatus)
                ->map(fn ($c, $status) => "{$status}: {$c}")
                ->implode(', ') . '.';

        return "There are {$count} quality record(s) in your scope (latest per appointment).{$breakdown}";
    }

    /**
     * @param  array<string, mixed>  $seo
     */
    private function formatSeoPendingAnswer(array $seo): string
    {
        $count = (int) ($seo['pending_count'] ?? 0);

        return "There are {$count} pending SEO audit(s) in your scope.";
    }

    private function formatCountAnswer(string $label, int $count, ?string $date): string
    {
        $dateSuffix = $date ? " on {$date}" : '';

        return "There are {$count} {$label}{$dateSuffix} in your scope.";
    }
}
