<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AppointmentSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentSettingController extends BaseApiController
{
    /**
     * Get all appointment settings
     */
    public function index(): JsonResponse
    {
        return $this->executeTransaction(function () {
            $settings = AppointmentSetting::getAllActive();

            return $this->successResponse($settings, 'Appointment settings retrieved successfully');
        }, 'Appointment settings retrieval');
    }

    /**
     * Get specific setting
     */
    public function show(string $key): JsonResponse
    {
        return $this->executeTransaction(function () use ($key) {
            $setting = AppointmentSetting::where('key', $key)
                ->where('is_active', true)
                ->first();

            if (!$setting) {
                return $this->errorResponse('Setting not found', 404);
            }

            $value = json_decode($setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $setting->value = $value;
            }

            return $this->successResponse($setting, 'Setting retrieved successfully');
        }, 'Setting retrieval', ['setting_key' => $key]);
    }

    /**
     * Update or create setting
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:100',
            'value' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $setting = AppointmentSetting::setValue(
                $request->key,
                $request->value,
                $request->description,
                $request->is_active ?? true
            );

            // Refresh to get the updated model
            $setting->refresh();

            // Decode JSON value if applicable
            $value = json_decode($setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $setting->value = $value;
            }

            return $this->successResponse($setting, 'Setting saved successfully', 201);
        }, 'Setting save');
    }

    /**
     * Update setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $key) {
            $setting = AppointmentSetting::setValue(
                $key,
                $request->value,
                $request->description,
                $request->is_active ?? true
            );

            // Refresh to get the updated model
            $setting->refresh();

            // Decode JSON value if applicable
            $value = json_decode($setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $setting->value = $value;
            }

            return $this->successResponse($setting, 'Setting updated successfully');
        }, 'Setting update', ['setting_key' => $key]);
    }

    /**
     * Delete setting
     */
    public function destroy(string $key): JsonResponse
    {
        return $this->executeTransaction(function () use ($key) {
            $setting = AppointmentSetting::where('key', $key)->first();

            if (!$setting) {
                return $this->errorResponse('Setting not found', 404);
            }

            $setting->delete();

            return $this->successResponse(null, 'Setting deleted successfully');
        }, 'Setting deletion', ['setting_key' => $key]);
    }

    /**
     * Initialize default settings
     */
    public function initializeDefaults(): JsonResponse
    {
        return $this->executeTransaction(function () {
            AppointmentSetting::initializeDefaults();

            $settings = AppointmentSetting::getAllActive();

            return $this->successResponse($settings, 'Default settings initialized successfully');
        }, 'Default settings initialization');
    }

    /**
     * Reset all settings to defaults
     */
    public function resetToDefaults(): JsonResponse
    {
        return $this->executeTransaction(function () {
            // Delete all existing settings
            AppointmentSetting::query()->delete();

            // Initialize defaults
            AppointmentSetting::initializeDefaults();

            $settings = AppointmentSetting::getAllActive();

            return $this->successResponse($settings, 'Settings reset to defaults successfully');
        }, 'Settings reset to defaults');
    }

    /**
     * Get settings summary
     */
    public function getSummary(): JsonResponse
    {
        return $this->executeTransaction(function () {
            $allSettings = AppointmentSetting::all();
            $activeSettings = $allSettings->where('is_active', true);
            $inactiveSettings = $allSettings->where('is_active', false);

            $summary = [
                'total_settings' => $allSettings->count(),
                'active_settings' => $activeSettings->count(),
                'inactive_settings' => $inactiveSettings->count(),
                'categories' => [
                    'booking' => $activeSettings->filter(fn($s) => str_contains($s->key, 'booking'))->count(),
                    'notification' => $activeSettings->filter(fn($s) => str_contains($s->key, 'notification'))->count(),
                    'rescheduling' => $activeSettings->filter(fn($s) => str_contains($s->key, 'rescheduling'))->count(),
                    'general' => $activeSettings->filter(fn($s) => !str_contains($s->key, ['booking', 'notification', 'rescheduling']))->count(),
                ],
                'recently_updated' => $activeSettings->sortByDesc('updated_at')->take(5)->values(),
            ];

            return $this->successResponse($summary, 'Settings summary retrieved successfully');
        }, 'Settings summary retrieval');
    }
}
