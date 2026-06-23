<?php

namespace App\Services\Search\Index;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\Search\SearchEntityType;
use App\Support\LeadDisplayId;
use Illuminate\Database\Eloquent\Model;

class GlobalSearchDocumentBuilder
{
    public function buildFromModel(Model $model): ?array
    {
        return match ($model::class) {
            FollowupBusiness::class => $this->fromBusiness($model),
            FollowupAuthPerson::class => $this->fromContact($model),
            Deal::class => $this->fromDeal($model),
            Appointment::class => $this->fromAppointment($model),
            User::class => $this->fromUser($model),
            Email::class => $this->fromEmail($model),
            Consultation::class => $this->fromConsultation($model),
            SeoDetail::class => $this->fromSeoAudit($model),
            Comment::class => $this->fromComment($model),
            default => null,
        };
    }

    public function documentIdForModel(Model $model): ?string
    {
        $entityType = $this->entityTypeForModel($model);

        if ($entityType === null) {
            return null;
        }

        return SearchEntityType::documentId($entityType, (string) $model->getKey());
    }

    public function entityTypeForModel(Model $model): ?string
    {
        return match ($model::class) {
            FollowupBusiness::class => SearchEntityType::BUSINESS,
            FollowupAuthPerson::class => SearchEntityType::CONTACT,
            Deal::class => SearchEntityType::DEAL,
            Appointment::class => SearchEntityType::APPOINTMENT,
            User::class => SearchEntityType::USER,
            Email::class => SearchEntityType::EMAIL,
            Consultation::class => SearchEntityType::CONSULTATION,
            SeoDetail::class => SearchEntityType::SEO_AUDIT,
            Comment::class => SearchEntityType::COMMENT,
            default => null,
        };
    }

    private function baseDocument(
        string $entityType,
        string|int $entityId,
        string $title,
        string $subtitle,
        string $searchText,
        string $route,
        array $metadata,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ): array {
        return [
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'title' => $title,
            'subtitle' => $subtitle,
            'search_text' => $searchText,
            'route' => $route,
            'metadata' => $metadata,
            'created_at' => ($createdAt ?? now())->format('c'),
            'updated_at' => ($updatedAt ?? now())->format('c'),
        ];
    }

    private function fromBusiness(FollowupBusiness $business): array
    {
        $searchText = implode(' ', array_filter([
            LeadDisplayId::format($business->id),
            $business->id,
            $business->name,
            $business->trading_name,
            $business->company_registration_number,
            $business->address_line1,
            $business->city,
            $business->postcode,
            $business->country,
            $business->category,
            $business->sub_category,
            $business->type,
            $business->source_name,
            $business->sub_source,
            $business->website,
        ]));

        return $this->baseDocument(
            SearchEntityType::BUSINESS,
            $business->id,
            $business->name ?? 'Business #' . $business->id,
            trim(implode(' • ', array_filter([$business->category, $business->sub_category, $business->type]))),
            $searchText,
            '/followup/businesses/' . $business->id,
            [
                'followup_business_id' => $business->id,
                'lead_display_id' => LeadDisplayId::format($business->id),
                'trading_name' => $business->trading_name,
                'company_registration_number' => $business->company_registration_number,
                'address_line1' => $business->address_line1,
                'city' => $business->city,
                'postcode' => $business->postcode,
                'country' => $business->country,
                'company_size' => $business->company_size,
                'category' => $business->category,
                'sub_category' => $business->sub_category,
                'type' => $business->type,
                'source_name' => $business->source_name,
                'sub_source' => $business->sub_source,
                'annual_revenue' => $business->annual_revenue,
                'number_of_locations' => $business->number_of_locations,
                'website' => $business->website,
            ],
            $business->created_at,
            $business->updated_at,
        );
    }

    private function fromContact(FollowupAuthPerson $contact): array
    {
        $fullName = trim("{$contact->firstname} {$contact->middlename} {$contact->lastname}");
        $searchText = implode(' ', array_filter([
            $contact->title,
            $contact->firstname,
            $contact->middlename,
            $contact->lastname,
            $contact->job_title,
            $contact->seniority_level,
            $contact->linkedin_profile,
            $contact->primaryphone,
            $contact->altphone,
            $contact->primarymobile,
            $contact->altmobile,
            $contact->primaryemail,
            $contact->altemail,
        ]));

        return $this->baseDocument(
            SearchEntityType::CONTACT,
            $contact->id,
            $fullName !== '' ? $fullName : 'Contact #' . $contact->id,
            trim(implode(' • ', array_filter([$contact->job_title, $contact->primaryemail, $contact->primarymobile]))),
            $searchText,
            '/followup/contacts/' . $contact->id,
            [
                'contact_id' => $contact->id,
                'full_name' => $fullName,
                'job_title' => $contact->job_title,
                'seniority_level' => $contact->seniority_level,
                'extension' => $contact->extension,
                'linkedin_profile' => $contact->linkedin_profile,
                'facebook_profile' => $contact->facebook_profile,
                'preferred_contact_method' => $contact->preferred_contact_method,
                'preferred_contact_time' => $contact->preferred_contact_time,
                'primary_email' => $contact->primaryemail,
                'primary_mobile' => $contact->primarymobile,
            ],
            $contact->created_at,
            $contact->updated_at,
        );
    }

    private function fromDeal(Deal $deal): array
    {
        $businessName = $deal->relationLoaded('followupBusiness')
            ? $deal->followupBusiness?->name
            : $deal->followupBusiness()->value('name');

        $searchText = implode(' ', array_filter([
            $deal->id,
            $deal->name,
            $deal->type,
            $deal->deal_stage,
            $deal->selected_service,
            $deal->lost_reason,
            $businessName,
        ]));

        return $this->baseDocument(
            SearchEntityType::DEAL,
            $deal->id,
            $deal->name ?? $deal->id,
            trim(implode(' • ', array_filter([$businessName, $deal->deal_stage, $deal->type]))),
            $searchText,
            '/deals/' . $deal->id,
            [
                'deal_id' => $deal->id,
                'followup_business_id' => $deal->followup_business_id,
                'auth_person_id' => $deal->auth_person_id,
                'deal_stage' => $deal->deal_stage,
                'type' => $deal->type,
                'selected_service' => $deal->selected_service,
            ],
            $deal->created_at,
            $deal->updated_at,
        );
    }

    private function fromAppointment(Appointment $appointment): array
    {
        $businessName = $appointment->relationLoaded('followupBusiness')
            ? $appointment->followupBusiness?->name
            : $appointment->followupBusiness()->value('name');

        $searchText = implode(' ', array_filter([
            $appointment->id,
            $appointment->status,
            $appointment->current_status,
            $businessName,
            $appointment->date?->format('Y-m-d'),
        ]));

        return $this->baseDocument(
            SearchEntityType::APPOINTMENT,
            $appointment->id,
            $appointment->id,
            trim(implode(' • ', array_filter([$businessName, $appointment->current_status, $appointment->date?->format('d M Y')]))),
            $searchText,
            '/appointments/' . $appointment->id,
            [
                'appointment_id' => $appointment->id,
                'followup_business_id' => $appointment->followup_business_id,
                'current_status' => $appointment->current_status,
                'date' => $appointment->date?->format('Y-m-d'),
            ],
            $appointment->created_at,
            $appointment->updated_at,
        );
    }

    private function fromUser(User $user): array
    {
        $fullName = trim("{$user->first_name} {$user->middle_name} {$user->last_name}");
        $searchText = implode(' ', array_filter([
            $fullName,
            $user->username,
            $user->email,
            $user->mobile,
            $user->emp_id,
            $user->designation,
            $user->user_type,
            $user->status,
        ]));

        return $this->baseDocument(
            SearchEntityType::USER,
            $user->id,
            $fullName !== '' ? $fullName : ($user->username ?? 'User #' . $user->id),
            trim(implode(' • ', array_filter([$user->email, $user->emp_id, $user->designation]))),
            $searchText,
            '/admin/users/' . $user->id,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'emp_id' => $user->emp_id,
                'status' => $user->status,
            ],
            $user->created_at,
            $user->updated_at,
        );
    }

    private function fromEmail(Email $email): array
    {
        $businessName = $email->relationLoaded('followupBusiness')
            ? $email->followupBusiness?->name
            : $email->followupBusiness()->value('name');

        $recipients = collect($email->to ?? [])->implode(' ');
        $searchText = implode(' ', array_filter([
            $email->type,
            $businessName,
            $recipients,
            collect($email->cc ?? [])->implode(' '),
        ]));

        return $this->baseDocument(
            SearchEntityType::EMAIL,
            $email->id,
            'Email #' . $email->id,
            trim(implode(' • ', array_filter([$businessName, $email->type]))),
            $searchText,
            '/emails/' . $email->id,
            [
                'email_id' => $email->id,
                'followup_business_id' => $email->followup_business_id,
                'type' => $email->type,
            ],
            $email->created_at,
            $email->updated_at,
        );
    }

    private function fromConsultation(Consultation $consultation): array
    {
        $appointmentId = $consultation->appointment_id;
        $searchText = implode(' ', array_filter([
            $consultation->id,
            $appointmentId,
            $consultation->status,
            $consultation->custom_status,
            $consultation->reason,
            $consultation->getAttributeValue('closer'),
        ]));

        return $this->baseDocument(
            SearchEntityType::CONSULTATION,
            $consultation->id,
            'Consultation #' . $consultation->id,
            trim(implode(' • ', array_filter([$appointmentId, $consultation->status, $consultation->reason]))),
            $searchText,
            '/consultations/' . $consultation->id,
            [
                'consultation_id' => $consultation->id,
                'appointment_id' => $appointmentId,
                'status' => $consultation->status,
            ],
            $consultation->created_at,
            $consultation->updated_at,
        );
    }

    private function fromSeoAudit(SeoDetail $seo): array
    {
        $businessName = $seo->relationLoaded('followupBusiness')
            ? $seo->followupBusiness?->name
            : $seo->followupBusiness()->value('name');

        $searchText = implode(' ', array_filter([
            $seo->id,
            $seo->status,
            $seo->reason,
            $seo->auditor,
            $seo->audited_website,
            $businessName,
        ]));

        return $this->baseDocument(
            SearchEntityType::SEO_AUDIT,
            $seo->id,
            'SEO Audit #' . $seo->id,
            trim(implode(' • ', array_filter([$businessName, $seo->status, $seo->audited_website]))),
            $searchText,
            '/seo-audits/' . $seo->id,
            [
                'seo_audit_id' => $seo->id,
                'followup_business_id' => $seo->followup_business_id,
                'status' => $seo->status,
                'audited_website' => $seo->audited_website,
            ],
            $seo->created_at,
            $seo->updated_at,
        );
    }

    private function fromComment(Comment $comment): array
    {
        $businessName = $comment->relationLoaded('followupBusiness')
            ? $comment->followupBusiness?->name
            : $comment->followupBusiness()->value('name');

        $searchText = implode(' ', array_filter([
            $comment->id,
            $comment->comment,
            $comment->old_status,
            $comment->new_status,
            $businessName,
        ]));

        return $this->baseDocument(
            SearchEntityType::COMMENT,
            $comment->id,
            'Comment on ' . ($businessName ?? 'Business'),
            trim(implode(' • ', array_filter([$businessName, $comment->new_status]))),
            $searchText,
            '/followup/businesses/' . $comment->followup_business_id,
            [
                'comment_id' => $comment->id,
                'followup_business_id' => $comment->followup_business_id,
                'new_status' => $comment->new_status,
            ],
            $comment->created_at,
            $comment->updated_at,
        );
    }
}
