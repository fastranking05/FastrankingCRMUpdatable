<?php

require_once 'vendor/autoload.php';

echo "=== Checking Auth Persons Duplication ===\n\n";

// Check auth persons for Business 1
$persons1 = DB::table('followup_business_auth_person')
    ->where('followup_business_id', 1)
    ->pluck('followup_auth_person_id')
    ->toArray();

echo "Business 1 auth persons: " . json_encode($persons1) . "\n";

// Check auth persons for Business 2
$persons2 = DB::table('followup_business_auth_person')
    ->where('followup_business_id', 2)
    ->pluck('followup_auth_person_id')
    ->toArray();

echo "Business 2 auth persons: " . json_encode($persons2) . "\n";

// Find common persons
$common = array_intersect($persons1, $persons2);
echo "Common persons: " . json_encode(array_values($common)) . "\n\n";

// Check auth person details
echo "=== Auth Person Details ===\n";
foreach ($common as $personId) {
    $person = DB::table('followup_auth_persons')
        ->where('id', $personId)
        ->first();
    
    echo "Person ID $personId: " . ($person ? $person->firstname . ' ' . $person->lastname : 'Not found') . "\n";
}

echo "\n=== Solution ===\n";
echo "The issue is that auth persons with IDs 1 and 2 are linked to BOTH businesses.\n";
echo "This is why they appear in both business records.\n";
echo "The unique() filter only works within each business's collection,\n";
echo "but the same person can legitimately be associated with multiple businesses.\n\n";

echo "To fix this, we need to ensure each business only shows its own auth persons.\n";

?>
