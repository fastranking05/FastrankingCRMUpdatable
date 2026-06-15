<?php

namespace Tests\Unit;

use App\Services\AI\ChatDeterministicAnswerService;
use PHPUnit\Framework\TestCase;

class ChatDeterministicAnswerServiceTest extends TestCase
{
    private ChatDeterministicAnswerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChatDeterministicAnswerService();
    }

    public function test_resolves_qa_approved_count_from_query_context(): void
    {
        $crmData = [
            'query_context' => [
                'quality_audits' => [
                    'qa_approved_count' => 1,
                    'qa_approved_appointments' => [
                        [
                            'appointment_id' => 'FRH3D0000004',
                            'business_name' => 'Allenhouse School Foundation',
                        ],
                    ],
                ],
            ],
        ];

        $answer = $this->service->tryResolve(
            'how many appointments are approved from quality',
            $crmData
        );

        $this->assertNotNull($answer);
        $this->assertStringContainsString('1 appointment', $answer);
        $this->assertStringContainsString('Allenhouse School Foundation', $answer);
        $this->assertStringNotContainsString('4 appointment', $answer);
    }

    public function test_does_not_confuse_conducted_with_qa_approved(): void
    {
        $crmData = [
            'query_context' => [
                'quality_audits' => [
                    'qa_approved_count' => 1,
                    'audit_qualified_count' => 3,
                    'by_appointment_status' => [
                        'QA-Approved' => 1,
                        'Conducted' => 2,
                    ],
                    'qa_approved_appointments' => [],
                ],
            ],
        ];

        $answer = $this->service->tryResolve(
            'how many appointments are approved from quality',
            $crmData
        );

        $this->assertNotNull($answer);
        $this->assertStringContainsString('1 appointment', $answer);
        $this->assertStringNotContainsString('3 appointment', $answer);
    }
}
