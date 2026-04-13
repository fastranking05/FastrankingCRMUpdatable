<?php

// Test the consultation API with new fields
echo "Testing Consultation API with New Fields...\n\n";

// Test 1: Create consultation with is_customer_available only
echo "Test 1: Create consultation with is_customer_available\n";
$test1Data = json_encode([
    'appointment_id' => 'FRMID00000001',
    'status' => 'scheduled',
    'custom_status' => 'Pending Review',
    'reason' => 'Customer requested consultation',
    'assigned_user' => 1,
    'is_customer_available' => 1,
]);

$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation");
curl_setopt($ch1, CURLOPT_POST, true);
curl_setopt($ch1, CURLOPT_POSTFIELDS, $test1Data);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1ODEzODY4LCJleHAiOjE3NzU4NTcwNjgsIm5iZiI6MTc3NTgxMzg2OCwianRpIjoiaDNnY05qOEZ1dkU0UGpDYyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.fxBF8OGEL-u3w36SaEhrzlhPs1dceoObZaR6YvIFVXo'
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP Status: " . $httpCode1 . "\n";
$data1 = json_decode($response1, true);
if ($data1 && $data1['success']) {
    echo "✓ Consultation created successfully\n";
    echo "Consultation ID: " . $data1['data']['id'] . "\n";
    echo "is_customer_available: " . ($data1['data']['is_customer_available'] ?? 'not set') . "\n";
} else {
    echo "✗ Failed to create consultation\n";
    echo "Error: " . ($data1['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 2: Create consultation with comments
echo "Test 2: Create consultation with comments array\n";
$test2Data = json_encode([
    'appointment_id' => 'FRMID00000001',
    'status' => 'scheduled',
    'custom_status' => 'Pending Review',
    'reason' => 'Customer requested consultation',
    'assigned_user' => 1,
    'is_customer_available' => 1,
    'comments' => [
        [
            'comment' => 'Initial inquiry received through website contact form. Client interested in enterprise solutions.',
            'old_status' => null,
            'new_status' => 'Followup'
        ],
        [
            'comment' => 'Follow-up call scheduled for next week.',
            'old_status' => 'Followup',
            'new_status' => 'Scheduled'
        ]
    ]
]);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation");
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $test2Data);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1ODEzODY4LCJleHAiOjE3NzU4NTcwNjgsIm5iZiI6MTc3NTgxMzg2OCwianRpIjoiaDNnY05qOEZ1dkU0UGpDYyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.fxBF8OGEL-u3w36SaEhrzlhPs1dceoObZaR6YvIFVXo'
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Status: " . $httpCode2 . "\n";
$data2 = json_decode($response2, true);
if ($data2 && $data2['success']) {
    echo "✓ Consultation created successfully\n";
    echo "Consultation ID: " . $data2['data']['id'] . "\n";
    echo "is_customer_available: " . ($data2['data']['is_customer_available'] ?? 'not set') . "\n";
    echo "Comments count in request: 2\n";
} else {
    echo "✗ Failed to create consultation\n";
    echo "Error: " . ($data2['message'] ?? 'Unknown error') . "\n";
    if (isset($data2['errors'])) {
        echo "Validation errors: " . json_encode($data2['errors']) . "\n";
    }
}

echo "\n";

// Test 3: Verify appointment current_status was updated
echo "Test 3: Verify appointment current_status was updated\n";
if ($data2 && $data2['success']) {
    $appointmentId = $data2['data']['appointment_id'];
    $expectedStatus = 'scheduled';
    
    // Get the appointment to verify current_status was updated
    $ch3 = curl_init();
    curl_setopt($ch3, CURLOPT_URL, "http://127.0.0.1:8000/api/appointment/{$appointmentId}");
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1ODEzODY4LCJleHAiOjE3NzU4NTcwNjgsIm5iZiI6MTc3NTgxMzg2OCwianRpIjoiaDNnY05qOEZ1dkU0UGpDYyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.fxBF8OGEL-u3w36SaEhrzlhPs1dceoObZaR6YvIFVXo'
    ]);
    
    $response3 = curl_exec($ch3);
    $httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    curl_close($ch3);
    
    if ($httpCode3 === 200) {
        $appointmentData = json_decode($response3, true);
        if ($appointmentData && $appointmentData['success']) {
            $currentStatus = $appointmentData['data']['current_status'] ?? 'not set';
            echo "Appointment current_status: " . $currentStatus . "\n";
            if ($currentStatus === $expectedStatus) {
                echo "✓ Appointment current_status successfully updated to: " . $currentStatus . "\n";
            } else {
                echo "✗ Appointment current_status not updated. Expected: " . $expectedStatus . ", Got: " . $currentStatus . "\n";
            }
        }
    } else {
        echo "✗ Failed to fetch appointment details (HTTP Status: " . $httpCode3 . ")\n";
    }
} else {
    echo "Skipping appointment status verification as consultation creation failed\n";
}

echo "\n=== TEST COMPLETED ===\n";
