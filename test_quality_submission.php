<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Quality\QualityDataSubmissionController;

// Test payload with quality_id in answers
$testPayload = [
    "auditstatus" => "qualified",
    "status" => "QA-Approved",
    "meetinglink" => "https://meet.example.com/quality-assessment-123",
    "score" => 85.50,
    "answers" => [
        [
            "quality_id" => 1,  // Manually provided quality_id
            "question_id" => 1,
            "answer" => "yes"
        ],
        [
            "quality_id" => 1,  // Manually provided quality_id
            "question_id" => 2,
            "answer" => "yes"
        ],
        [
            "quality_id" => 1,  // Manually provided quality_id
            "question_id" => 3,
            "answer" => "partially done"
        ]
    ],
    "comments" => [
        [
            "followup_business_id" => 1,
            "comment" => "dfjghdkghkdfghk",
            "old_status" => "QA-Pending",
            "new_status" => "QA-Approved"
        ]
    ]
];

echo "Test Payload:\n";
echo json_encode($testPayload, JSON_PRETTY_PRINT) . "\n\n";

echo "Validation Rules:\n";
echo "- auditstatus: required (qualified/unqualified)\n";
echo "- status: required (string)\n";
echo "- meetinglink: optional (string)\n";
echo "- score: optional (numeric, 0-100)\n";
echo "- answers: required (array, min 1)\n";
echo "- answers.*.quality_id: required (exists in qualities table)\n";
echo "- answers.*.question_id: required (exists in quality_questions table)\n";
echo "- answers.*.answer: required (yes/no/partially done/not applicable)\n";
echo "- comments: optional (array)\n";
echo "- comments.*.followup_business_id: required with comments (exists in followup_businesses)\n";
echo "- comments.*.comment: required with comments (string)\n";
echo "- comments.*.old_status: required with comments (string)\n";
echo "- comments.*.new_status: required with comments (string)\n\n";

echo "API Endpoint: POST /api/admin/quality-data-submission\n";
echo "Headers: Authorization: Bearer {jwt_token}, Content-Type: application/json\n\n";

echo "The controller will:\n";
echo "1. Validate all fields including quality_id in answers\n";
echo "2. Create Quality record with provided auditstatus, status, etc.\n";
echo "3. Create QualityAnswer records using manually provided quality_id\n";
echo "4. Create Comment records using manually provided followup_business_id\n";
echo "5. Return response with quality data and comments\n";

?>
