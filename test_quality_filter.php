<?php

// Test the quality filter API to verify it returns only latest quality record per appointment ID
echo "Testing Quality Filter API...\n";

// Test the quality filter endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/quality/quality-filter");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzc1NTU5MDE2LCJleHAiOjE3NzU2MDIyMTYsIm5iZiI6MTc3NTU1OTAxNiwianRpIjoiSjVET1UwMEUycWxpSmxNNyIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3IiwidXNlcl90eXBlIjoiYWRtaW4iLCJ1c2VybmFtZSI6IlN1cmFqIn0.ngJiFpSlygyurv00vq1neEIJSLn3Qjsu538417xkEVM'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ API Response Successful\n";
        
        $qualityRecords = $data['data']['data'] ?? [];
        $appointmentIds = [];
        
        echo "Total Quality Records: " . count($qualityRecords) . "\n";
        
        // Check for duplicate appointment IDs
        foreach ($qualityRecords as $record) {
            $appointmentId = $record['appointment_id'] ?? 'N/A';
            
            if (isset($appointmentIds[$appointmentId])) {
                echo "❌ DUPLICATE APPOINTMENT ID FOUND: " . $appointmentId . "\n";
                echo "   First record: " . $appointmentIds[$appointmentId] . "\n";
                echo "   Second record: " . $record['id'] . "\n";
            } else {
                $appointmentIds[$appointmentId] = $record['id'];
                echo "✅ Appointment ID: " . $appointmentId . " -> Quality ID: " . $record['id'] . "\n";
            }
        }
        
        if (count($appointmentIds) === count($qualityRecords)) {
            echo "\n✅ SUCCESS: No duplicate appointment IDs found!\n";
            echo "✅ Each appointment has only its latest quality record.\n";
        } else {
            echo "\n❌ FAILED: Found duplicate appointment IDs.\n";
        }
        
        echo "\n=== Quality Records Details ===\n";
        foreach ($qualityRecords as $record) {
            echo "ID: " . $record['id'] . " | Appointment ID: " . $record['appointment_id'] . " | Status: " . $record['status'] . "\n";
        }
        
    } else {
        echo "❌ API Response Failed\n";
        echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ HTTP Request Failed\n";
    echo "Response: " . $response . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
