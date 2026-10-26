<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends BaseApiController
{
    private const IMAGE_FIELDS = ['dark_mode_logo', 'light_mode_logo', 'sidepanel_logo', 'favicon'];

    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp'];

    private const MAX_IMAGE_SIZE_KB = 2048;

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    private function ensureAdminOrMaster(): ?JsonResponse
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->user_type, ['admin', 'master_admin'], true)) {
            return $this->errorResponse('Access denied. Only Admin or Master Admin can manage settings.', 403);
        }

        return null;
    }

    /**
     * Move uploaded logo/favicon files to public/uploads/settings and swap
     * them for their stored path in the payload. Returns a JsonResponse on
     * invalid files, otherwise the prepared data array.
     */
    private function prepareSettingData(Request $request): array|JsonResponse
    {
        $data = $request->all();

        foreach (self::IMAGE_FIELDS as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);

            if (! $file->isValid() || $file->getSize() === 0) {
                unset($data[$field]);

                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)
                || $file->getSize() > self::MAX_IMAGE_SIZE_KB * 1024) {
                return $this->errorResponse('Validation failed', 422, [
                    $field => ['The '.str_replace('_', ' ', $field).' must be an image (jpg, jpeg, png, gif, svg, ico, webp) up to 2MB.'],
                ]);
            }

            $filename = $field.'_'.time().'_'.uniqid().'.'.$extension;
            $file->move(public_path('uploads/settings'), $filename);
            $data[$field] = 'uploads/settings/'.$filename;
        }

        return $data;
    }

    public function index(): JsonResponse
    {
        if ($denied = $this->ensureAdminOrMaster()) {
            return $denied;
        }

        $settings = $this->settingService->listAll();

        return $this->successResponse($settings, 'Settings retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdminOrMaster()) {
            return $denied;
        }

        $data = $this->prepareSettingData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $validator = Validator::make($data, [
            'dark_mode_logo' => 'nullable|string|max:255',
            'light_mode_logo' => 'nullable|string|max:255',
            'sidepanel_logo' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'whitelist_ips' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($data) {
            $setting = $this->settingService->create($data);

            return $this->successResponse($setting, 'Setting created successfully', 201);
        }, 'Setting creation', $request->only([
            'company_name', 'primary_color', 'secondary_color',
        ]));
    }

    public function show(string $id): JsonResponse
    {
        if ($denied = $this->ensureAdminOrMaster()) {
            return $denied;
        }

        $setting = $this->settingService->find((int) $id);

        if (! $setting) {
            return $this->errorResponse('Setting not found', 404);
        }

        return $this->successResponse($setting, 'Setting retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->ensureAdminOrMaster()) {
            return $denied;
        }

        $setting = $this->settingService->find((int) $id);

        if (! $setting) {
            return $this->errorResponse('Setting not found', 404);
        }

        $data = $this->prepareSettingData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $validator = Validator::make($data, [
            'dark_mode_logo' => 'sometimes|nullable|string|max:255',
            'light_mode_logo' => 'sometimes|nullable|string|max:255',
            'sidepanel_logo' => 'sometimes|nullable|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'favicon' => 'sometimes|nullable|string|max:255',
            'primary_color' => 'sometimes|nullable|string|max:20',
            'secondary_color' => 'sometimes|nullable|string|max:20',
            'whitelist_ips' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($data, $setting) {
            $this->settingService->update($setting, $data);

            $setting->load('creator:id,first_name,last_name');

            return $this->successResponse($setting, 'Setting updated successfully');
        }, 'Setting update', ['setting_id' => $setting->id]);
    }

    public function destroy(string $id): JsonResponse
    {
        if ($denied = $this->ensureAdminOrMaster()) {
            return $denied;
        }

        $setting = $this->settingService->find((int) $id);

        if (! $setting) {
            return $this->errorResponse('Setting not found', 404);
        }

        return $this->executeTransaction(function () use ($setting) {
            $this->settingService->delete($setting);

            return $this->successResponse(null, 'Setting deleted successfully');
        }, 'Setting deletion', ['setting_id' => $setting->id]);
    }
}
