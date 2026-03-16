<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use App\Models\TimeSlot;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class FollowupTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Create or get test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'middle_name' => null,
                'last_name' => 'User',
                'gender' => 'male',
                'dob' => '1990-01-01',
                'mobile' => '+1234567890',
                'username' => 'testuser',
                'password' => bcrypt('password'),
                'date_of_joining' => '2020-01-01',
                'emp_id' => 'EMP001',
                'status' => 'active',
                'user_type' => 'admin',
                'designation' => 'System Administrator',
                'created_by' => null,
            ]
        );

        // Create time slots for appointments
        $timeSlots = [];
        for ($i = 1; $i <= 10; $i++) {
            $timeSlots[] = TimeSlot::firstOrCreate([
                'name' => "Slot $i",
                'start_time' => sprintf('%02d:00:00', 8 + $i),
                'end_time' => sprintf('%02d:00:00', 9 + $i),
                'duration_minutes' => 60,
                'is_active' => true,
                'max_concurrent_bookings' => 3,
            ]);
        }

        echo "Creating 100 follow-up records...\n";

        // Create 100 follow-up businesses with associated data
        for ($i = 1; $i <= 100; $i++) {
            DB::beginTransaction();
            
            try {
                // Create business
                $business = FollowupBusiness::create([
                    'name' => $faker->company(),
                    'category' => $faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Retail', 'Manufacturing']),
                    'type' => $faker->randomElement(['Standard', 'Premium', 'Enterprise']),
                    'website' => $faker->url(),
                    'phone' => $faker->unique()->phoneNumber(),
                    'email' => $faker->unique()->companyEmail(),
                    'created_by' => $user->id,
                ]);

                // Create 1-3 auth persons per business
                $authPersonCount = $faker->numberBetween(1, 3);
                $authPersonIds = [];
                
                for ($j = 1; $j <= $authPersonCount; $j++) {
                    $title = $faker->randomElement(['Mr.', 'Ms.', 'Dr.', 'Prof.']);
                    $gender = in_array($title, ['Mr.', 'Dr.']) ? 'male' : 'female';
                    
                    $authPerson = FollowupAuthPerson::create([
                        'title' => $title,
                        'firstname' => $faker->firstName($gender),
                        'middlename' => $faker->optional(0.3)->firstName(),
                        'lastname' => $faker->lastName(),
                        'is_primary' => $j === 1, // First person is primary
                        'designation' => $faker->jobTitle(),
                        'gender' => $gender,
                        'dob' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                        'primaryphone' => $faker->unique()->phoneNumber(),
                        'altphone' => $faker->optional(0.5)->phoneNumber(),
                        'primarymobile' => $faker->unique()->phoneNumber(),
                        'altmobile' => $faker->optional(0.3)->phoneNumber(),
                        'primaryemail' => $faker->unique()->email(),
                        'altemail' => $faker->optional(0.3)->email(),
                        'created_by' => $user->id,
                    ]);
                    
                    $authPersonIds[] = $authPerson->id;
                }

                // Associate auth persons with business
                $business->authPersons()->attach($authPersonIds);

                // Create 2-5 follow-up details per business
                $followupDetailCount = $faker->numberBetween(2, 5);
                for ($k = 1; $k <= $followupDetailCount; $k++) {
                    FollowupDetail::create([
                        'followup_business_id' => $business->id,
                        'source' => $faker->randomElement(['Website', 'Email', 'Phone', 'Referral', 'Social Media', 'Cold Call', 'Event', 'Partner']),
                        'status' => $faker->randomElement(['New', 'Contacted', 'In Progress', 'Qualified', 'Follow-up Required', 'Converted', 'Lost']),
                        'date' => $faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                        'time' => $faker->time('H:i'),
                        'created_by' => $user->id,
                    ]);
                }

                // Create 1-3 comments per business
                $commentCount = $faker->numberBetween(1, 3);
                for ($l = 1; $l <= $commentCount; $l++) {
                    Comment::create([
                        'followup_business_id' => $business->id,
                        'comment' => $faker->sentence(10) . ' ' . $faker->sentence(8),
                        'old_status' => $faker->optional(0.7)->randomElement(['New', 'Contacted', 'In Progress']),
                        'new_status' => $faker->randomElement(['Contacted', 'In Progress', 'Qualified', 'Follow-up Required']),
                        'created_by' => $user->id,
                    ]);
                }

                // Create appointments for 30% of businesses
                if ($faker->boolean(30)) {
                    $timeSlot = $faker->randomElement($timeSlots);
                    
                    Appointment::create([
                        'followup_business_id' => $business->id,
                        'source' => $faker->randomElement(['Follow-up', 'Direct', 'Referral', 'Website']),
                        'status' => $faker->randomElement(['Appointment Booked', 'Appointment Rebooked']),
                        'date' => $faker->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
                        'time_slot_id' => $timeSlot->id,
                        'current_status' => $faker->randomElement(['Booked', 'Confirmed', 'In Progress', 'Conducted', 'Not Conducted', 'Rescheduled', 'Cancelled']),
                        'created_by' => $user->id,
                    ]);
                }

                DB::commit();
                
                if ($i % 10 === 0) {
                    echo "Created {$i} follow-up records...\n";
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "Error creating record {$i}: " . $e->getMessage() . "\n";
                continue;
            }
        }

        echo "Successfully created 100 follow-up records with associated data!\n";
        
        // Display summary statistics
        $this->displayStatistics();
    }

    /**
     * Display statistics of created data
     */
    private function displayStatistics(): void
    {
        echo "\n=== Database Statistics ===\n";
        echo "Follow-up Businesses: " . FollowupBusiness::count() . "\n";
        echo "Auth Persons: " . FollowupAuthPerson::count() . "\n";
        echo "Follow-up Details: " . FollowupDetail::count() . "\n";
        echo "Comments: " . Comment::count() . "\n";
        echo "Appointments: " . Appointment::count() . "\n";
        echo "Time Slots: " . TimeSlot::count() . "\n";
        echo "========================\n\n";
        
        echo "Sample businesses created:\n";
        $sampleBusinesses = FollowupBusiness::take(5)->get();
        foreach ($sampleBusinesses as $business) {
            echo "- {$business->name} ({$business->category}, {$business->type})\n";
        }
        
        echo "\nSample auth persons created:\n";
        $sampleAuthPersons = FollowupAuthPerson::take(5)->get();
        foreach ($sampleAuthPersons as $person) {
            echo "- {$person->title} {$person->firstname} {$person->lastname} ({$person->designation})\n";
        }
        
        echo "\nSample follow-up details created:\n";
        $sampleDetails = FollowupDetail::take(5)->get();
        foreach ($sampleDetails as $detail) {
            echo "- {$detail->source} -> {$detail->status} ({$detail->date})\n";
        }
        
        echo "\nSample appointments created:\n";
        $sampleAppointments = Appointment::take(5)->get();
        foreach ($sampleAppointments as $appointment) {
            echo "- {$appointment->source} on {$appointment->date} ({$appointment->current_status})\n";
        }
    }
}
