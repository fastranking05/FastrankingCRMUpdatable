<?php

// Test the consultation filter API
echo "Testing Consultation Filter API...\n\n";

// Test 1: Get filter options
echo "Test 1: Get filter options\n";
$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter-options");
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP Status: " . $httpCode1 . "\n";
$data1 = json_decode($response1, true);
if ($data1 && $data1['success']) {
    echo "✓ Filter options retrieved successfully\n";
    echo "Date filters count: " . count($data1['data']['date_filters']) . "\n";
    echo "Status options count: " . count($data1['data']['status_options']) . "\n";
} else {
    echo "✗ Failed to get filter options\n";
    echo "Error: " . ($data1['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 2: Filter consultations with status
echo "Test 2: Filter consultations by status\n";
$test2Data = http_build_query([
    'status' => 'scheduled',
]);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter?" . $test2Data);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Status: " . $httpCode2 . "\n";
$data2 = json_decode($response2, true);
if ($data2 && $data2['success']) {
    echo "✓ Consultations filtered successfully\n";
    echo "Total records: " . count($data2['data']) . "\n";
    if (count($data2['data']) > 0) {
        echo "First consultation ID: " . $data2['data'][0]['id'] . "\n";
        echo "First consultation status: " . $data2['data'][0]['status'] . "\n";
    }
} else {
    echo "✗ Failed to filter consultations\n";
    echo "Error: " . ($data2['message'] ?? 'Unknown error') . "\n";
    if (isset($data2['errors'])) {
        echo "Validation errors: " . json_encode($data2['errors']) . "\n";
    }
}

echo "\n";

// Test 3: Filter consultations with assigned_user
echo "Test 3: Filter consultations by assigned user\n";
$test3Data = http_build_query([
    'assigned_user' => 1,
]);

$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter?" . $test3Data);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "HTTP Status: " . $httpCode3 . "\n";
$data3 = json_decode($response3, true);
if ($data3 && $data3['success']) {
    echo "✓ Consultations filtered successfully\n";
    echo "Total records: " . count($data3['data']) . "\n";
    if (count($data3['data']) > 0) {
        echo "First consultation assigned_user: " . $data3['data'][0]['assigned_user']['id'] ?? 'null' . "\n";
    }
} else {
    echo "✗ Failed to filter consultations\n";
    echo "Error: " . ($data3['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 4: Filter consultations with is_customer_available
echo "Test 4: Filter consultations by customer availability\n";
$test4Data = http_build_query([
    'is_customer_available' => 1,
]);

$ch4 = curl_init();
curl_setopt($ch4, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter?" . $test4Data);
curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch4, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response4 = curl_exec($ch4);
$httpCode4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);
curl_close($ch4);

echo "HTTP Status: " . $httpCode4 . "\n";
$data4 = json_decode($response4, true);
if ($data4 && $data4['success']) {
    echo "✓ Consultations filtered successfully\n";
    echo "Total records: " . count($data4['data']) . "\n";
    if (count($data4['data']) > 0) {
        echo "First consultation is_customer_available: " . $data4['data'][0]['is_customer_available'] . "\n";
    }
} else {
    echo "✗ Failed to filter consultations\n";
    echo "Error: " . ($data4['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 5: Filter consultations with date filter
echo "Test 5: Filter consultations by date (this_month)\n";
$test5Data = http_build_query([
    'date_filter' => 'this_month',
]);

$ch5 = curl_init();
curl_setopt($ch5, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter?" . $test5Data);
curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch5, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response5 = curl_exec($ch5);
$httpCode5 = curl_getinfo($ch5, CURLINFO_HTTP_CODE);
curl_close($ch5);

echo "HTTP Status: " . $httpCode5 . "\n";
$data5 = json_decode($response5, true);
if ($data5 && $data5['success']) {
    echo "✓ Consultations filtered successfully\n";
    echo "Total records: " . count($data5['data']) . "\n";
} else {
    echo "✗ Failed to filter consultations\n";
    echo "Error: " . ($data5['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 6: Filter consultations with combined filters
echo "Test 6: Filter consultations with combined filters\n";
$test6Data = http_build_query([
    'status' => 'scheduled',
    'assigned_user' => 1,
    'is_customer_available' => 1,
]);

$ch6 = curl_init();
curl_setopt($ch6, CURLOPT_URL, "http://127.0.0.1:8000/api/consultation/filter?" . $test6Data);
curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch6, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc2MDcwMTY3LCJleHAiOjE3NzYxMTMzNjcsIm5iZiI6MTc3NjA3MDE2NywianRpIjoieXVnOTlkYXp3dzRId0tiaiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.lVnOIAzNrveE4Lebp02KSzXgmi3FyGAEGUGMvsMWuRI'
]);

$response6 = curl_exec($ch6);
$httpCode6 = curl_getinfo($ch6, CURLINFO_HTTP_CODE);
curl_close($ch6);

echo "HTTP Status: " . $httpCode6 . "\n";
$data6 = json_decode($response6, true);
if ($data6 && $data6['success']) {
    echo "✓ Consultations filtered successfully\n";
    echo "Total records: " . count($data6['data']) . "\n";
    if (count($data6['data']) > 0) {
        echo "First consultation status: " . $data6['data'][0]['status'] . "\n";
        echo "First consultation is_customer_available: " . $data6['data'][0]['is_customer_available'] . "\n";
    }
} else {
    echo "✗ Failed to filter consultations\n";
    echo "Error: " . ($data6['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
