<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Quality;
use App\Models\QualityAnswer;
use App\Models\QualityQuestion;
use App\Models\SeoDetail;
use App\Models\SeoQuestion;
use App\Models\SeoQuestionAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class FiveKDummyDataSeeder extends Seeder
{
    private $faker;
    private $user;
    private $timeSlots;
    private $seoQuestions;
    private $qualityQuestions;

    private const TOTAL_LEADS = 5000;
    private const CHUNK_SIZE = 250;

    // Pre-generated reference data arrays
    private $categories;
    private $types;
    private $sources;
    private $statuses;
    private $titles;
    private $firstNamesMale;
    private $firstNamesFemale;
    private $lastNames;
    private $designations;
    private $websiteDomains;
    private $cities;

    // Custom ID counters
    private $fridCounter = 0;
    private $frmidCounter = 0;

    public function run(): void
    {
        $this->faker = Faker::create();

        echo "============================================\n";
        echo "  5K DUMMY DATA SEEDER STARTING\n";
        echo "  Target: " . self::TOTAL_LEADS . " leads with full relationships\n";
        echo "============================================\n\n";

        $this->prepareReferenceData();
        $this->ensurePrerequisites();
        $this->initCustomIdCounters();

        $startTime = microtime(true);

        for ($chunk = 0; $chunk < self::TOTAL_LEADS; $chunk += self::CHUNK_SIZE) {
            $chunkNum = $chunk / self::CHUNK_SIZE + 1;
            echo "  Processing chunk {$chunkNum}...\n";

            $this->processChunk($chunk);

            $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);
            $progress = min($chunk + self::CHUNK_SIZE, self::TOTAL_LEADS);
            echo "    Leads " . ($chunk + 1) . "-{$progress} done. Memory: {$memoryUsage} MB\n";
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        echo "\nAll " . self::TOTAL_LEADS . " leads generated in {$elapsed} seconds.\n\n";

        $this->displayStatistics();
    }

    // ========================
    //  PREPARATION
    // ========================

    private function prepareReferenceData(): void
    {
        $this->categories = ['Technology', 'Healthcare', 'Finance', 'Education', 'Retail', 'Manufacturing', 'Real Estate', 'Hospitality', 'Legal', 'Marketing', 'Construction', 'Transport', 'Energy', 'Agriculture', 'Insurance', 'Media', 'Telecom', 'Pharma', 'Logistics', 'Consulting'];
        $this->types = ['Standard', 'Premium', 'Enterprise', 'Startup', 'SME'];
        $this->sources = ['Website', 'Email', 'Phone', 'Referral', 'Social Media', 'Cold Call', 'Event', 'Partner', 'Walk-in', 'Chat'];
        $this->statuses = ['New', 'Contacted', 'In Progress', 'Qualified', 'Follow-up Required', 'Converted', 'Lost', 'On Hold'];
        $this->titles = ['Mr.', 'Ms.', 'Dr.', 'Prof.'];
        $this->designations = ['CEO', 'CTO', 'CFO', 'COO', 'VP of Sales', 'Marketing Director', 'Product Manager', 'Engineering Lead', 'Operations Manager', 'Business Development Manager', 'HR Director', 'Sales Manager', 'Account Manager', 'Project Manager', 'Team Lead', 'Senior Developer', 'UX Designer', 'Data Analyst', 'IT Manager', 'Consultant'];
        $this->lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Anderson', 'Taylor', 'Thomas', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Green'];
        $this->firstNamesMale = ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Charles', 'Thomas', 'Daniel', 'Matthew', 'Anthony', 'Mark', 'Steven', 'Paul', 'Andrew', 'Joshua', 'Kevin', 'Brian'];
        $this->firstNamesFemale = ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Barbara', 'Elizabeth', 'Susan', 'Jessica', 'Sarah', 'Karen', 'Lisa', 'Nancy', 'Betty', 'Margaret', 'Sandra', 'Ashley', 'Kimberly', 'Emily', 'Donna', 'Michelle'];
        $this->cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'Austin', 'London', 'Manchester', 'Birmingham', 'Liverpool', 'Glasgow', 'Edinburgh', 'Toronto', 'Vancouver', 'Sydney', 'Melbourne'];
        $this->websiteDomains = ['techcorp', 'healthplus', 'financesolutions', 'edulearn', 'retailhub', 'manufacturepro', 'realtygroup', 'hospitalityinn', 'legalfirm', 'marketgenius', 'buildexpert', 'transitlogix', 'energywise', 'agrogrow', 'insurancesafe', 'mediasphere', 'teleconnect', 'pharmalife', 'logistixpro', 'consultwise'];
    }

    private function ensurePrerequisites(): void
    {
        // Ensure test user exists
        $this->user = User::firstOrCreate(
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
        echo "User ensured: {$this->user->email} (ID: {$this->user->id})\n";

        // Ensure time slots exist (call AppointmentSystemSeeder if needed)
        if (TimeSlot::count() === 0) {
            $this->call(AppointmentSystemSeeder::class);
        }
        $this->timeSlots = TimeSlot::where('is_active', true)->get()->values();
        echo "Time slots: " . $this->timeSlots->count() . "\n";

        // Ensure SEO questions exist
        if (SeoQuestion::count() === 0) {
            $seoQuestionsData = [
                ['name' => 'Website Speed Score (1-100)', 'answer_type' => 'number', 'is_active' => true],
                ['name' => 'Mobile Responsiveness', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Excellent', 'Good', 'Fair', 'Poor', 'Not Mobile-Friendly']), 'is_active' => true],
                ['name' => 'SSL Certificate Present', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Yes', 'No', 'Expired']), 'is_active' => true],
                ['name' => 'Meta Title Optimization', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Excellent', 'Good', 'Needs Improvement', 'Missing']), 'is_active' => true],
                ['name' => 'Meta Description Quality', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Excellent', 'Good', 'Needs Improvement', 'Missing']), 'is_active' => true],
                ['name' => 'Keyword Density Analysis', 'answer_type' => 'text', 'is_active' => true],
                ['name' => 'Backlink Count', 'answer_type' => 'number', 'is_active' => true],
                ['name' => 'Domain Authority (DA)', 'answer_type' => 'number', 'is_active' => true],
                ['name' => 'Page Authority (PA)', 'answer_type' => 'number', 'is_active' => true],
                ['name' => 'XML Sitemap Present', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Yes', 'No']), 'is_active' => true],
                ['name' => 'Robots.txt Configuration', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Proper', 'Issues Found', 'Missing']), 'is_active' => true],
                ['name' => 'Image Alt Tags Optimization', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['All Optimized', 'Partially', 'None']), 'is_active' => true],
                ['name' => 'Content Quality Assessment', 'answer_type' => 'textarea', 'is_active' => true],
                ['name' => 'URL Structure Rating', 'answer_type' => 'dropdown', 'dropdown_options' => json_encode(['Excellent', 'Good', 'Fair', 'Poor']), 'is_active' => true],
                ['name' => 'Internal Linking Score (1-10)', 'answer_type' => 'number', 'is_active' => true],
            ];
            foreach ($seoQuestionsData as $q) {
                $q['created_by'] = $this->user->id;
                SeoQuestion::create($q);
            }
        }
        $this->seoQuestions = SeoQuestion::where('is_active', true)->get();
        echo "SEO questions: " . $this->seoQuestions->count() . "\n";

        // Ensure quality questions exist
        if (QualityQuestion::count() === 0) {
            $this->call(QualityControlSeeder::class);
        }
        $this->qualityQuestions = QualityQuestion::where('is_active', true)->get();
        if ($this->qualityQuestions->isEmpty()) {
            $this->qualityQuestions = QualityQuestion::all();
        }
        echo "Quality questions: " . $this->qualityQuestions->count() . "\n\n";
    }

    private function initCustomIdCounters(): void
    {
        $latestFrid = FollowupDetail::orderBy('id', 'desc')->first();
        $this->fridCounter = $latestFrid ? (int) substr($latestFrid->id, 4) : 0;

        $latestFrmid = Appointment::orderBy('id', 'desc')->first();
        $this->frmidCounter = $latestFrmid ? (int) substr($latestFrmid->id, 5) : 0;
    }

    // ========================
    //  MAIN CHUNK PROCESSOR
    // ========================

    private function processChunk(int $startIndex): void
    {
        $endIndex = min($startIndex + self::CHUNK_SIZE, self::TOTAL_LEADS);
        $chunkCount = $endIndex - $startIndex;

        // --- STEP 1: Prepare business data ---
        $businesses = [];
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $businesses[] = $this->makeBusiness($i);
        }

        // --- STEP 2: Insert businesses ---
        FollowupBusiness::insert($businesses);
        $firstBizId = FollowupBusiness::orderBy('id', 'desc')->limit($chunkCount)
            ->pluck('id')->last();
        // Build array of business IDs in order
        $businessIds = [];
        for ($b = 0; $b < $chunkCount; $b++) {
            $businessIds[] = $firstBizId + $b;
        }

        // --- STEP 3: Prepare and insert auth persons + pivots ---
        $authPersons = [];
        $personIdx = 0;
        for ($i = 0; $i < $chunkCount; $i++) {
            $globalIdx = $startIndex + $i;
            $count = ($globalIdx % 3) + 1;
            for ($j = 0; $j < $count; $j++) {
                $authPersons[] = $this->makeAuthPerson($globalIdx, $j);
            }
        }
        FollowupAuthPerson::insert($authPersons);
        $firstPersonId = FollowupAuthPerson::orderBy('id', 'desc')->limit(count($authPersons))
            ->pluck('id')->last();
        $personIds = [];
        for ($p = 0; $p < count($authPersons); $p++) {
            $personIds[] = $firstPersonId + $p;
        }

        // Business-Auth Person pivots
        $pivots = [];
        $pIdx = 0;
        for ($i = 0; $i < $chunkCount; $i++) {
            $globalIdx = $startIndex + $i;
            $count = ($globalIdx % 3) + 1;
            for ($j = 0; $j < $count; $j++) {
                if (isset($personIds[$pIdx])) {
                    $pivots[] = [
                        'followup_business_id' => $businessIds[$i],
                        'followup_auth_person_id' => $personIds[$pIdx],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $pIdx++;
            }
        }
        if (!empty($pivots)) {
            DB::table('followup_business_auth_person')->insert($pivots);
        }

        // --- STEP 4: Prepare and insert followup details ---
        $followupDetails = [];
        for ($i = 0; $i < $chunkCount; $i++) {
            $globalIdx = $startIndex + $i;
            $detailCount = ($globalIdx % 4) + 2;
            for ($k = 0; $k < $detailCount; $k++) {
                $this->fridCounter++;
                $followupDetails[] = $this->makeFollowupDetail($businessIds[$i], $globalIdx, $k);
            }
        }
        if (!empty($followupDetails)) {
            FollowupDetail::insert($followupDetails);
            foreach ($businessIds as $bizId) {
                FollowupBusiness::refreshLatestFollowupSortFromDetails((int) $bizId);
            }
        }

        // --- STEP 5: Prepare and insert comments ---
        $comments = [];
        for ($i = 0; $i < $chunkCount; $i++) {
            $globalIdx = $startIndex + $i;
            $commentCount = ($globalIdx % 3) + 1;
            for ($l = 0; $l < $commentCount; $l++) {
                $comments[] = $this->makeComment($businessIds[$i], $globalIdx, $l);
            }
        }
        if (!empty($comments)) {
            Comment::insert($comments);
        }

        // --- STEP 6: Appointments, Consultations, Quality, SEO ---
        $appointments = [];
        $consultations = [];
        $qualities = [];
        $qualityAnswers = [];
        $seoDetails = [];
        $seoAnswers = [];

        // Track which business indices get appointments (for linking)
        $aptBusinessIds = [];
        $aptIds = [];
        $aptIndices = [];

        for ($i = 0; $i < $chunkCount; $i++) {
            $globalIdx = $startIndex + $i;
            $bizId = $businessIds[$i];

            // ~30% get appointments
            $hasApt = ($globalIdx % 10) < 3;
            if ($hasApt) {
                $timeSlot = $this->timeSlots[$i % $this->timeSlots->count()];
                $this->frmidCounter++;
                $aptId = 'FRMID' . str_pad($this->frmidCounter, 8, '0', STR_PAD_LEFT);

                $appointments[] = $this->makeAppointment($bizId, $aptId, $timeSlot, $globalIdx);
                $aptBusinessIds[] = $bizId;
                $aptIds[] = $aptId;
                $aptIndices[] = $globalIdx;
            }

            // ~30% get SEO (different indices from appointments: 5,6,7 out of 10)
            $hasSeo = ($globalIdx % 10) >= 5 && ($globalIdx % 10) < 8;
            if ($hasSeo) {
                $seoDetails[] = $this->makeSeoDetail($bizId, $globalIdx);
            }
        }

        // Insert appointments
        if (!empty($appointments)) {
            Appointment::insert($appointments);
        }

        // Build consultations for each appointment
        foreach ($aptIndices as $idx => $globalIdx) {
            $bizId = $aptBusinessIds[$idx];
            $aptId = $aptIds[$idx];
            $consultations[] = $this->makeConsultation($aptId, $bizId, $globalIdx);

            // ~50% of appointments get quality audits
            if ($globalIdx % 2 === 0) {
                $qualities[] = $this->makeQuality($aptId, $globalIdx);
            }
        }

        // Insert consultations
        if (!empty($consultations)) {
            Consultation::insert($consultations);
        }

        // Insert qualities
        if (!empty($qualities)) {
            Quality::insert($qualities);
        }

        // Resolve quality IDs and build answers
        if (!empty($qualities)) {
            $qApptIds = array_column($qualities, 'appointment_id');
            $insertedQualities = Quality::whereIn('appointment_id', $qApptIds)->orderBy('id')->get();

            $allQaData = [];
            foreach ($insertedQualities as $qIdx => $quality) {
                $globalIdx = $qIdx * 2; // rough mapping
                $answerCount = ($qIdx % 3) + 3;
                $qc = $this->qualityQuestions->count();
                for ($a = 0; $a < $answerCount && $a < $qc; $a++) {
                    $allQaData[] = [
                        'quality_id' => $quality->id,
                        'question_id' => $this->qualityQuestions[$a]->id,
                        'answers' => $this->pickQualityAnswer($qIdx, $a),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($allQaData)) {
                QualityAnswer::insert($allQaData);
            }
        }

        // Insert SEO details
        if (!empty($seoDetails)) {
            SeoDetail::insert($seoDetails);
        }

        // Resolve SEO detail IDs and build answers
        if (!empty($seoDetails)) {
            $seoBizIds = array_column($seoDetails, 'followup_business_id');
            $insertedSeoDetails = SeoDetail::whereIn('followup_business_id', $seoBizIds)
                ->orderBy('id')->get();

            $allSeoAns = [];
            foreach ($insertedSeoDetails as $sIdx => $seoDetail) {
                $answerCount = ($sIdx % 6) + 5;
                $sc = $this->seoQuestions->count();
                $actualCount = min($answerCount, $sc);
                for ($a = 0; $a < $actualCount; $a++) {
                    $allSeoAns[] = [
                        'seo_details_id' => $seoDetail->id,
                        'seo_question_id' => $this->seoQuestions[$a]->id,
                        'answer' => $this->generateSeoAnswer($this->seoQuestions[$a], $sIdx),
                        'comments' => ($a % 3 === 0) ? 'Automated audit finding #' . ($sIdx + $a + 1) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($allSeoAns)) {
                SeoQuestionAnswer::insert($allSeoAns);
            }
        }
    }

    // ========================
    //  DATA FACTORY METHODS
    // ========================

    private function makeBusiness(int $idx): array
    {
        $cat = $this->categories[$idx % count($this->categories)];
        $type = $this->types[$idx % count($this->types)];
        $domainIdx = $idx % count($this->websiteDomains);
        $cityIdx = $idx % count($this->cities);
        $num = $idx + 1;

        return [
            'name' => $this->cities[$cityIdx] . ' ' . ucfirst($this->websiteDomains[$domainIdx]) . ' ' . $num,
            'category' => $cat,
            'type' => $type,
            'website' => 'https://www.' . $this->websiteDomains[$domainIdx] . $num . '.com',
            'created_by' => $this->user->id,
            'created_at' => $this->randomDate('2024-01-01', '2026-05-19'),
            'updated_at' => now(),
        ];
    }

    private function makeAuthPerson(int $idx, int $j): array
    {
        $titleIdx = ($idx + $j) % count($this->titles);
        $title = $this->titles[$titleIdx];
        $gender = in_array($title, ['Mr.', 'Dr.']) ? 'male' : 'female';
        $firstName = $gender === 'male'
            ? $this->firstNamesMale[($idx * 3 + $j) % count($this->firstNamesMale)]
            : $this->firstNamesFemale[($idx * 3 + $j) % count($this->firstNamesFemale)];
        $lastName = $this->lastNames[($idx * 5 + $j) % count($this->lastNames)];
        $personNum = ($idx * 3) + $j + 1;
        $domainIdx = $idx % count($this->websiteDomains);

        return [
            'title' => $title,
            'firstname' => $firstName,
            'middlename' => ($j % 3 === 0) ? chr(65 + ($idx % 26)) : null,
            'lastname' => $lastName,
            'is_primary' => $j === 0 ? 1 : 0,
            'job_title' => $this->designations[($idx + $j) % count($this->designations)],
            'gender' => $gender,
            'dob' => $this->randomDob(),
            'primaryphone' => '+1' . str_pad(mt_rand(3000000000, 9999999999), 10, '0', STR_PAD_LEFT),
            'altphone' => ($j % 2 === 0) ? '+1' . str_pad(mt_rand(3000000000, 9999999999), 10, '0', STR_PAD_LEFT) : null,
            'primarymobile' => '+1' . str_pad(mt_rand(3000000000, 9999999999), 10, '0', STR_PAD_LEFT),
            'altmobile' => ($j % 3 === 0) ? '+1' . str_pad(mt_rand(3000000000, 9999999999), 10, '0', STR_PAD_LEFT) : null,
            'primaryemail' => strtolower($firstName . '.' . $lastName . $personNum . mt_rand(100, 999) . '@' . $this->websiteDomains[$domainIdx] . '.com'),
            'altemail' => ($j % 3 === 1) ? strtolower($firstName . $j . $personNum . mt_rand(100, 999) . '@gmail.com') : null,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeFollowupDetail(int $businessId, int $idx, int $k): array
    {
        return [
            'id' => 'FRID' . str_pad($this->fridCounter, 8, '0', STR_PAD_LEFT),
            'followup_business_id' => $businessId,
            'source' => $this->sources[($idx + $k) % count($this->sources)],
            'status' => $this->statuses[($idx + $k) % count($this->statuses)],
            'date' => $this->randomDate('2024-06-01', '2026-05-19'),
            'time' => sprintf('%02d:%02d:00', rand(8, 18), rand(0, 3) * 15),
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeComment(int $businessId, int $idx, int $l): array
    {
        $oldStatus = $this->statuses[($idx + $l) % count($this->statuses)];
        $newStatus = $this->statuses[($idx + $l + 1) % count($this->statuses)];

        return [
            'followup_business_id' => $businessId,
            'comment' => $this->generateComment($idx, $l),
            'old_status' => ($l > 0) ? $oldStatus : null,
            'new_status' => $newStatus,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeAppointment(int $businessId, string $aptId, $timeSlot, int $idx): array
    {
        return [
            'id' => $aptId,
            'followup_business_id' => $businessId,
            'source' => $this->sources[$idx % count($this->sources)],
            'status' => ($idx % 5 === 0) ? 'Appointment Rebooked' : 'Appointment Booked',
            'date' => $this->randomDate('2026-05-01', '2026-08-31'),
            'time_slot_id' => $timeSlot->id,
            'current_status' => $this->getRandomAppointmentStatus($idx),
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeConsultation(string $aptId, int $businessId, int $idx): array
    {
        return [
            'appointment_id' => $aptId,
            'status' => $this->getRandomConsultationStatus($idx),
            'custom_status' => null,
            'reason' => $this->generateConsultationReason($idx),
            'meeting_date' => $this->randomDate('2026-05-01', '2026-08-31'),
            'meeting_slot' => $this->timeSlots[($idx + 3) % $this->timeSlots->count()]->id,
            'closer' => ($idx % 3 === 0) ? $this->user->id : null,
            'conducted_date' => ($idx % 3 === 0) ? $this->randomDate('2026-05-01', '2026-06-19') : null,
            'assigned_user' => $this->user->id,
            'is_customer_available' => ($idx % 4 !== 0) ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeQuality(string $aptId, int $idx): array
    {
        return [
            'appointment_id' => $aptId,
            'auditstatus' => ($idx % 5 === 0) ? 'unqualified' : 'qualified',
            'status' => $this->getRandomQualityStatus($idx),
            'assigned_user' => $this->user->id,
            'meeting_link' => ($idx % 3 === 0) ? 'https://meet.google.com/abc-' . str_pad($idx, 4, '0', STR_PAD_LEFT) : null,
            'score' => $this->getRandomScore($idx),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeSeoDetail(int $businessId, int $idx): array
    {
        return [
            'followup_business_id' => $businessId,
            'status' => $this->getRandomSeoStatus($idx),
            'reason' => $this->generateSeoReason($idx),
            'audited_website' => 'https://www.example' . ($idx + 1) . '.com',
            'audited_date' => $this->randomDate('2026-03-01', '2026-05-19'),
            'auditor' => 'SEO Auditor ' . ($idx % 5 + 1),
            'assigned_user' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // ========================
    //  VALUE HELPERS
    // ========================

    private function randomDate(string $start, string $end): string
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        return date('Y-m-d', mt_rand($startTs, $endTs));
    }

    private function randomDob(): string
    {
        return date('Y-m-d', mt_rand(strtotime('-60 years'), strtotime('-18 years')));
    }

    private function generateComment(int $idx, int $commentIdx): string
    {
        $templates = [
            'Followed up with client regarding their inquiry about our services.',
            'Client requested more information on pricing and packages.',
            'Sent proposal document to client for review.',
            'Client is interested but needs to discuss with management.',
            'Scheduled a callback for next week to discuss further.',
            'Client provided positive feedback on initial consultation.',
            'Updated contact information for the business.',
            'Client requested a meeting with senior management.',
            'Sent follow-up email with additional resources.',
            'Client confirmed readiness to proceed with services.',
        ];
        return $templates[($idx + $commentIdx) % count($templates)];
    }

    private function generateConsultationReason(int $idx): string
    {
        $reasons = [
            'Client requested a consultation to discuss digital marketing strategy.',
            'Follow-up consultation for SEO improvement plan.',
            'Initial consultation for website optimization services.',
            'Client wants to explore social media marketing options.',
            'Consultation for PPC campaign setup and management.',
            'Business growth strategy consultation.',
            'Client requested review of current marketing performance.',
            'Consultation for brand positioning strategy.',
            'Client needs advice on content marketing approach.',
            'Strategy session for Q3 marketing initiatives.',
        ];
        return $reasons[$idx % count($reasons)];
    }

    private function getRandomAppointmentStatus(int $idx): string
    {
        $s = ['Booked', 'Confirmed', 'In Progress', 'Conducted', 'Not Conducted', 'Rescheduled', 'Cancelled'];
        return $s[$idx % count($s)];
    }

    private function getRandomConsultationStatus(int $idx): string
    {
        $s = ['Pending', 'Scheduled', 'Completed', 'Rescheduled', 'Not Conducted', 'In Progress'];
        return $s[$idx % count($s)];
    }

    private function getRandomQualityStatus(int $idx): string
    {
        $s = ['QA-Pending', 'QA-Approved', 'QA-Rejected', 'QA-In Review'];
        return $s[$idx % count($s)];
    }

    private function getRandomSeoStatus(int $idx): string
    {
        $s = ['Pending', 'In Progress', 'Completed', 'Needs Review', 'Approved', 'Rejected'];
        return $s[$idx % count($s)];
    }

    private function getRandomScore(int $idx): float
    {
        $scores = [65.5, 72.0, 88.3, 91.0, 45.5, 78.2, 95.0, 82.7, 59.1, 100.0, 33.3, 70.5, 85.0, 93.8, 50.0, 76.4];
        return $scores[$idx % count($scores)];
    }

    private function pickQualityAnswer(int $idx, int $a): string
    {
        $answers = ['yes', 'no', 'partially done', 'not applicable'];
        return $answers[($idx + $a) % count($answers)];
    }

    private function generateSeoReason(int $idx): string
    {
        $reasons = [
            'Routine SEO audit for performance tracking.',
            'Client requested comprehensive SEO analysis.',
            'Post-launch SEO audit for new website.',
            'Quarterly SEO health check.',
            'SEO audit before major site redesign.',
            'Competitive analysis SEO audit.',
        ];
        return $reasons[$idx % count($reasons)];
    }

    private function generateSeoAnswer(SeoQuestion $q, int $idx): string
    {
        $type = $q->answer_type ?? 'text';

        switch ($type) {
            case 'number':
                $name = strtolower($q->name);
                if (str_contains($name, 'speed') || str_contains($name, 'score')) {
                    return (string) mt_rand(30, 100);
                }
                if (str_contains($name, 'backlink')) {
                    return (string) mt_rand(10, 5000);
                }
                if (str_contains($name, 'authority') || str_contains($name, 'da') || str_contains($name, 'pa')) {
                    return (string) mt_rand(1, 100);
                }
                return (string) mt_rand(1, 100);

            case 'dropdown':
                $options = $q->dropdown_options;
                if (is_string($options)) {
                    $options = json_decode($options, true);
                }
                if (is_array($options) && !empty($options)) {
                    return $options[$idx % count($options)];
                }
                return ($idx % 2 === 0) ? 'Yes' : 'No';

            case 'textarea':
                return 'Detailed analysis for ' . $q->name . ' - Assessment #' . ($idx + 1) . '. Findings indicate ' . (($idx % 3 === 0) ? 'good' : (($idx % 3 === 1) ? 'needs improvement' : 'excellent')) . ' performance.';

            default:
                $textAnswers = ['Optimized', 'Needs work', 'Adequate', 'Fully compliant', 'Partially optimized'];
                return $textAnswers[$idx % count($textAnswers)];
        }
    }

    // ========================
    //  STATISTICS
    // ========================

    private function displayStatistics(): void
    {
        echo "============================================\n";
        echo "  DATABASE STATISTICS AFTER 5K INSERT\n";
        echo "============================================\n\n";

        $bizCount = FollowupBusiness::count();
        $personCount = FollowupAuthPerson::count();
        $detailCount = FollowupDetail::count();
        $commentCount = Comment::count();
        $pivotCount = DB::table('followup_business_auth_person')->count();
        $aptCount = Appointment::count();
        $tsCount = TimeSlot::count();
        $consCount = Consultation::count();
        $qualCount = Quality::count();
        $qqCount = QualityQuestion::count();
        $qaCount = QualityAnswer::count();
        $seoCount = SeoDetail::count();
        $sqCount = SeoQuestion::count();
        $sqaCount = SeoQuestionAnswer::count();

        echo "=== LEADS (Followup) ===\n";
        echo "  Follow-up Businesses: " . number_format($bizCount) . "\n";
        echo "  Auth Persons:         " . number_format($personCount) . "\n";
        echo "  Follow-up Details:    " . number_format($detailCount) . "\n";
        echo "  Comments:             " . number_format($commentCount) . "\n";
        echo "  Business-Auth Pivots: " . number_format($pivotCount) . "\n\n";

        echo "=== APPOINTMENTS ===\n";
        echo "  Appointments:         " . number_format($aptCount) . "\n";
        echo "  Time Slots:           " . number_format($tsCount) . "\n\n";

        echo "=== CONSULTATIONS ===\n";
        echo "  Consultations:        " . number_format($consCount) . "\n\n";

        echo "=== QUALITY (Audit) ===\n";
        echo "  Quality Audits:       " . number_format($qualCount) . "\n";
        echo "  Quality Questions:    " . number_format($qqCount) . "\n";
        echo "  Quality Answers:      " . number_format($qaCount) . "\n\n";

        echo "=== SEO ===\n";
        echo "  SEO Details:          " . number_format($seoCount) . "\n";
        echo "  SEO Questions:        " . number_format($sqCount) . "\n";
        echo "  SEO Answers:          " . number_format($sqaCount) . "\n\n";

        $total = $bizCount + $personCount + $detailCount + $commentCount + $aptCount
            + $consCount + $qualCount + $qaCount + $seoCount + $sqaCount + $pivotCount;

        echo "============================================\n";
        echo "  TOTAL RECORDS: " . number_format($total) . "\n";
        echo "============================================\n";

        // Sample records
        echo "\n=== SAMPLE LEAD ===\n";
        $sample = FollowupBusiness::with('authPersons')->inRandomOrder()->first();
        if ($sample) {
            echo "  Business: {$sample->name} ({$sample->category})\n";
            echo "  Website: {$sample->website}\n";
            echo "  Auth Persons: " . $sample->authPersons->count() . "\n";
            foreach ($sample->authPersons->take(2) as $p) {
                echo "    - {$p->full_name} ({$p->job_title})\n";
            }
        }

        echo "\n=== SAMPLE APPOINTMENT ===\n";
        $sampleApt = Appointment::with(['followupBusiness', 'timeSlot'])->inRandomOrder()->first();
        if ($sampleApt) {
            echo "  ID: {$sampleApt->id}\n";
            echo "  Business: " . ($sampleApt->followupBusiness->name ?? 'N/A') . "\n";
            echo "  Date: {$sampleApt->date}\n";
            echo "  Slot: " . ($sampleApt->timeSlot->name ?? 'N/A') . "\n";
            echo "  Status: {$sampleApt->current_status}\n";
        }

        echo "\n=== SAMPLE CONSULTATION ===\n";
        $sampleCons = Consultation::inRandomOrder()->first();
        if ($sampleCons) {
            echo "  Status: {$sampleCons->status}\n";
            echo "  Reason: {$sampleCons->reason}\n";
            echo "  Meeting Date: {$sampleCons->meeting_date}\n";
        }

        echo "\n=== SAMPLE SEO AUDIT ===\n";
        $sampleSeo = SeoDetail::with('questionAnswers')->inRandomOrder()->first();
        if ($sampleSeo) {
            echo "  Business ID: {$sampleSeo->followup_business_id}\n";
            echo "  Status: {$sampleSeo->status}\n";
            echo "  Audited: {$sampleSeo->audited_date}\n";
            echo "  Answers: " . $sampleSeo->questionAnswers->count() . "\n";
        }

        echo "\n=== SAMPLE QUALITY AUDIT ===\n";
        $sampleQual = Quality::with('answers')->inRandomOrder()->first();
        if ($sampleQual) {
            echo "  Appointment: {$sampleQual->appointment_id}\n";
            echo "  Status: {$sampleQual->status}\n";
            echo "  Audit Status: {$sampleQual->auditstatus}\n";
            echo "  Score: {$sampleQual->score}\n";
            echo "  Answers: " . $sampleQual->answers->count() . "\n";
        }

        echo "\n✅ 5K DUMMY DATA SEEDING COMPLETE!\n";
        echo "============================================\n";
    }
}