<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Email;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Services\Zoom\ZoomMeetingGenerationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ConsultationMeetingService
{
    public function __construct(
        private readonly ZoomMeetingGenerationService $zoomMeetingGenerationService,
    ) {}

    /**
     * @return array{meeting_link: string, email_sent: bool, client_email: string|null}
     */
    public function setupMeetingForConsultation(
        Consultation $consultation,
        User $assignedUser,
        Appointment $appointment,
        ?int $emailCreatedBy = null,
    ): array {
        $appointment->loadMissing('followupBusiness.authPersons', 'timeSlot');

        $meeting = $this->zoomMeetingGenerationService->generateForUser(
            $assignedUser,
            $this->buildConsultationMeetingDetails($consultation, $appointment),
        );

        $consultation->update([
            'meeting_link' => $meeting['join_url'],
        ]);

        $zoomAccount = ZoomAccount::find($meeting['zoom_account_id']);
        $clientEmail = $this->resolveClientEmail($appointment);
        $emailSent = false;

        if ($clientEmail && $zoomAccount) {
            $emailSent = $this->sendMeetingEmail(
                $clientEmail,
                $assignedUser,
                $zoomAccount,
                $appointment,
                $consultation,
                $meeting,
                $emailCreatedBy,
            );
        } else {
            Log::warning('Consultation meeting created but client email not found', [
                'consultation_id' => $consultation->id,
                'appointment_id' => $appointment->id,
            ]);
        }

        return [
            'meeting_link' => $meeting['join_url'],
            'email_sent' => $emailSent,
            'client_email' => $clientEmail,
        ];
    }

    /**
     * @return array{
     *     topic: string,
     *     start_time: string,
     *     duration: int,
     *     agenda: string
     * }
     */
    public function buildConsultationMeetingDetails(Consultation $consultation, Appointment $appointment): array
    {
        $businessName = $appointment->followupBusiness?->name ?? 'Client';

        return [
            'topic' => "Sales Consultation - {$businessName}",
            'start_time' => $this->resolveMeetingStartTime($consultation, $appointment),
            'duration' => $appointment->timeSlot?->duration_minutes ?: 30,
            'agenda' => "Consultation for appointment {$appointment->id}",
        ];
    }

    private function resolveMeetingStartTime(Consultation $consultation, Appointment $appointment): string
    {
        $timezone = config('zoom.default_timezone');
        $date = $consultation->meeting_date ?? $appointment->date;

        if (!$date) {
            return now()->timezone($timezone)->addHour()->format('Y-m-d\TH:i:s');
        }

        $timeValue = $appointment->timeSlot?->start_time;

        if ($timeValue instanceof \DateTimeInterface) {
            $timeString = $timeValue->format('H:i:s');
        } elseif (is_string($timeValue) && $timeValue !== '') {
            $timeString = date('H:i:s', strtotime($timeValue));
        } else {
            $timeString = '10:00:00';
        }

        $dateString = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : (string) $date;

        return "{$dateString}T{$timeString}";
    }

    private function resolveClientEmail(Appointment $appointment): ?string
    {
        $email = $appointment->followupBusiness?->primaryAuthPerson()?->primaryemail;

        if (!is_string($email) || trim($email) === '') {
            $email = $appointment->followupBusiness?->authPersons
                ->first(fn ($person) => !empty($person->primaryemail))
                ?->primaryemail;
        }

        $email = is_string($email) ? trim($email) : null;

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * @param array{join_url: string, start_url: string|null, id: int|string, password: string|null} $meeting
     */
    private function sendMeetingEmail(
        string $clientEmail,
        User $assignedUser,
        ZoomAccount $zoomAccount,
        Appointment $appointment,
        Consultation $consultation,
        array $meeting,
        ?int $emailCreatedBy,
    ): bool {
        try {
            $appointment->loadMissing('timeSlot', 'followupBusiness');
            $contact = $appointment->followupBusiness?->primaryAuthPerson();
            $contactName = trim(($contact?->firstname ?? '') . ' ' . ($contact?->lastname ?? ''));
            $contactName = $contactName !== '' ? $contactName : 'Client';

            $salesName = trim($assignedUser->first_name . ' ' . $assignedUser->last_name);
            $businessName = $appointment->followupBusiness?->name ?? 'your business';
            $meetingDate = ($consultation->meeting_date ?? $appointment->date)?->format('d M Y') ?? 'TBC';
            $slotName = $appointment->timeSlot?->name;
            $meetingTime = $slotName;

            if ($appointment->timeSlot?->start_time) {
                $start = $appointment->timeSlot->start_time;
                $startFormatted = $start instanceof \DateTimeInterface
                    ? $start->format('H:i')
                    : date('H:i', strtotime((string) $start));
                $meetingTime = $slotName ? "{$slotName} ({$startFormatted})" : $startFormatted;
            }

            $passwordLine = !empty($meeting['password'])
                ? "\nMeeting Password: {$meeting['password']}"
                : '';

            $emailBody = implode("\n", array_filter([
                "Dear {$contactName},",
                '',
                "Your sales consultation for {$businessName} has been scheduled.",
                "Date: {$meetingDate}",
                $meetingTime ? "Time: {$meetingTime}" : null,
                "Sales Representative: {$salesName}",
                '',
                'Please join your Zoom meeting using the link below:',
                $meeting['join_url'],
                trim($passwordLine) ?: null,
                '',
                'If you have any questions, please reply to this email.',
                '',
                'Best regards,',
                $salesName,
            ]));

            $subject = "Your Sales Consultation Meeting - {$businessName}";

            Mail::raw($emailBody, function ($message) use ($clientEmail, $subject, $zoomAccount, $salesName, $assignedUser) {
                $message->to($clientEmail)->subject($subject);

                $fromAddress = config('mail.from.address');
                $fromName = config('mail.from.name') ?: $salesName;

                if ($fromAddress) {
                    $message->from($fromAddress, $fromName);
                }

                $replyTo = $zoomAccount->email ?: $assignedUser->email;
                if ($replyTo) {
                    $message->replyTo($replyTo, $salesName);
                }
            });

            if ($appointment->followup_business_id) {
                Email::create([
                    'followup_business_id' => $appointment->followup_business_id,
                    'to' => [$clientEmail],
                    'cc' => null,
                    'bcc' => null,
                    'type' => 'consultation_meeting',
                    'created_by' => $emailCreatedBy,
                ]);
            }

            Log::info('Consultation meeting email sent', [
                'consultation_id' => $consultation->id,
                'client_email' => $clientEmail,
                'assigned_user_id' => $assignedUser->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send consultation meeting email', [
                'consultation_id' => $consultation->id,
                'client_email' => $clientEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
