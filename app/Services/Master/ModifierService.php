<?php

namespace App\Services\Master;

use App\Models\Master\ModifierGroup;
use Illuminate\Support\Facades\DB;

class ModifierService
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function createGroup(array $data)
    {
        return DB::transaction(function () use ($data) {
            $group = ModifierGroup::create([
                'business_id' => $data['business_id'],
                'name' => $data['name'],
                'selection_type' => $data['selection_type'],
                'max_select' => $data['max_select'] ?? null,
                'is_required' => $data['is_required'] ?? false,
            ]);

            if (isset($data['options'])) {
                foreach ($data['options'] as $option) {
                    $group->options()->create([
                        'name' => $option['name'],
                        'additional_price' => $option['additional_price'] ?? 0,
                        'is_default' => $option['is_default'] ?? false,
                    ]);
                }
            }

            $this->auditLogService->log($group->business_id, 'modifier_group', $group->id, 'created', null, $group->load('options')->toArray());
            return $group;
        });
    }

    public function updateGroup(ModifierGroup $group, array $data)
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update([
                'name' => $data['name'],
                'selection_type' => $data['selection_type'],
                'max_select' => $data['max_select'] ?? null,
                'is_required' => $data['is_required'] ?? false,
            ]);

            $group->options()->delete();
            if (isset($data['options'])) {
                foreach ($data['options'] as $option) {
                    $group->options()->create([
                        'name' => $option['name'],
                        'additional_price' => $option['additional_price'] ?? 0,
                        'is_default' => $option['is_default'] ?? false,
                    ]);
                }
            }

            $this->auditLogService->log($group->business_id, 'modifier_group', $group->id, 'updated', null, $group->load('options')->toArray());
            return $group;
        });
    }

    public function deleteGroup(ModifierGroup $group)
    {
        return DB::transaction(function () use ($group) {
            $group->options()->delete();
            $group->delete();

            $this->auditLogService->log($group->business_id, 'modifier_group', $group->id, 'deleted');
        });
    }
}
