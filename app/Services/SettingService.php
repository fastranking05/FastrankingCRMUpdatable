<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function listAll()
    {
        return Setting::with('creator:id,first_name,last_name')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function find(int $id): ?Setting
    {
        return Setting::with('creator:id,first_name,last_name')->find($id);
    }

    public function create(array $data): Setting
    {
        $data['created_by'] = auth()->id();

        return Setting::create($data);
    }

    public function update(Setting $setting, array $data): bool
    {
        return $setting->update($data);
    }

    public function delete(Setting $setting): bool
    {
        return $setting->delete();
    }
}
