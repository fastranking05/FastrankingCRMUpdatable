<?php

// Test the quality filter API with appointment date filter
echo "Testing Quality Filter API with Appointment Date Filter...\n";

// Test 1: Test without any filters to see if quality records exist
echo "Test 1: No filters\n";
$test1Data = json_encode(['per_page' => 15]);

$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, "http://127.0.0.1:8000/api/quality/quality-filter");
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
echo "Total records: " . ($data1['data']['total'] ?? 0) . "\n";

if ($data1 && isset($data1['data']['data']) && !empty($data1['data']['data'])) {
    echo "Sample record:\n";
    $sample = $data1['data']['data'][0];
    echo "  ID: " . $sample['id'] . "\n";
    echo "  Appointment ID: " . $sample['appointment_id'] . "\n";
    echo "  Status: " . $sample['status'] . "\n";
    if (isset($sample['appointment'])) {
        echo "  Appointment Date: " . $sample['appointment']['date'] . "\n";
    }
}

echo "\n";

// Test 2: Test with appointment date filter only
echo "Test 2: Appointment date filter only\n";
$test2Data = json_encode([
    'date_filter' => 'today',
    'appointments' => ['date'],
    'per_page' => 15
]);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "http://127.0.0.1:8000/api/quality/quality-filter");
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
echo "Total records: " . ($data2['data']['total'] ?? 0) . "\n";

echo "\n";

// Test 3: Test with status filter only
echo "Test 3: Status filter only\n";
$test3Data = json_encode([
    'status' => 'QA-Approved',
    'per_page' => 15
]);

$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, "http://127.0.0.1:8000/api/quality/quality-filter");
curl_setopt($ch3, CURLOPT_POST, true);
curl_setopt($ch3, CURLOPT_POSTFIELDS, $test3Data);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1ODEzODY4LCJleHAiOjE3NzU4NTcwNjgsIm5iZiI6MTc3NTgxMzg2OCwianRpIjoiaDNnY05qOEZ1dkU0UGpDYyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.fxBF8OGEL-u3w36SaEhrzlhPs1dceoObZaR6YvIFVXo'
]);

$response3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "HTTP Status: " . $httpCode3 . "\n";
$data3 = json_decode($response3, true);
echo "Total records: " . ($data3['data']['total'] ?? 0) . "\n";

echo "\n";

// Test 4: Test with both filters (the user's request)
echo "Test 4: Both filters (user's request)\n";
$test4Data = json_encode([
    'date_filter' => 'today',
    'appointments' => ['date'],
    'status' => 'QA-Approved',
    'per_page' => 15
]);

$ch4 = curl_init();
curl_setopt($ch4, CURLOPT_URL, "http://127.0.0.1:8000/api/quality/quality-filter");
curl_setopt($ch4, CURLOPT_POST, true);
curl_setopt($ch4, CURLOPT_POSTFIELDS, $test4Data);
curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch4, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1ODEzODY4LCJleHAiOjE3NzU4NTcwNjgsIm5iZiI6MTc3NTgxMzg2OCwianRpIjoiaDNnY05qOEZ1dkU0UGpDYyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.fxBF8OGEL-u3w36SaEhrzlhPs1dceoObZaR6YvIFVXo'
]);

$response4 = curl_exec($ch4);
$httpCode4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);
curl_close($ch4);

echo "HTTP Status: " . $httpCode4 . "\n";
$data4 = json_decode($response4, true);
echo "Total records: " . ($data4['data']['total'] ?? 0) . "\n";

echo "\n=== TEST COMPLETED ===\n";
