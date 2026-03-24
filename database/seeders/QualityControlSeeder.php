<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Module;
use App\Models\QualityQuestion;
use App\Models\Role;
use Illuminate\Database\Seeder;

class QualityControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Quality Control Module
        $qualityModule = Module::firstOrCreate(
            ['name' => 'Quality Control'],
            [
                'description' => 'Quality Control module for appointment audits',
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        // Create Quality Control Department
        $qualityDept = Department::firstOrCreate(
            ['name' => 'Quality Control'],
            [
                'description' => 'Quality Control department for appointment audits',
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        // Create Quality Control Role
        $qualityRole = Role::firstOrCreate(
            ['name' => 'Quality Control'],
            [
                'description' => 'Quality Control role for appointment audits',
                'status' => 'active',
                'created_by' => 1,
            ]
        );

        // Attach module to role
        $qualityRole->modules()->syncWithoutDetaching([$qualityModule->id]);

        // Create sample quality questions
        $questions = [
            [
                'question' => 'Was the appointment started on time?',
                'created_by' => 1,
            ],
            [
                'question' => 'Was the customer greeted professionally?',
                'created_by' => 1,
            ],
            [
                'question' => 'Were all required documents collected?',
                'created_by' => 1,
            ],
            [
                'question' => 'Was the appointment process explained clearly?',
                'created_by' => 1,
            ],
            [
                'question' => 'Was the customer satisfied with the service?',
                'created_by' => 1,
            ],
        ];

        foreach ($questions as $questionData) {
            QualityQuestion::firstOrCreate(
                ['question' => $questionData['question']],
                $questionData
            );
        }

        $this->command->info('Quality Control module, department, role, and sample questions created successfully!');
    }
}
