<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\RoleTemplateEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\StoreRoleRequest;
use App\Http\Requests\App\Settings\UpdateRoleRequest;
use App\Models\Role;
use App\Services\App\Role\RoleProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('role.view');

        $search = $request->query('search');

        $roles = Role::where('business_id', $request->user()->business_id)
            ->withCount('users')
            ->with(['permissions:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('label', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Role $role) {
                $permissions = $role->permissions;
                $permissionsCount = $permissions->count();

                $groupCounts = [];
                foreach ($permissions as $permission) {
                    $permEnum = PermissionEnum::tryFrom($permission->name);
                    if (! $permEnum) {
                        continue;
                    }

                    $grpKey = $permEnum->group();
                    $grpLabel = $permEnum->groupLabel();

                    if (! isset($groupCounts[$grpKey])) {
                        $groupCounts[$grpKey] = [
                            'key' => $grpKey,
                            'label' => $grpLabel,
                            'count' => 0,
                        ];
                    }

                    $groupCounts[$grpKey]['count']++;
                }

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'is_default' => $role->is_default,
                    'users_count' => $role->users_count,
                    'permissions_count' => $permissionsCount,
                    'summary_groups' => array_values($groupCounts),
                ];
            });

        $businessType = $request->user()->business?->businessType?->code ?? 'general';

        return inertia('Settings/Role/Index', [
            'roles' => $roles,
            'filters' => $request->only(['search']),
            'templates' => RoleTemplateEnum::formattedList(),
            'businessType' => $businessType,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Role $role)
    {
        $this->authorize('role.view');

        if ($role->business_id !== $request->user()->business_id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'id' => $role->id,
            'label' => $role->label,
            'name' => $role->name,
            'is_default' => $role->is_default,
            'permissions' => $role->permissions()->pluck('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        $roleName = Str::slug($data['label']);

        // Ensure unique name within business
        $count = 1;
        $originalName = $roleName;
        while (Role::where('business_id', $request->user()->business_id)->where('name', $roleName)->exists()) {
            $roleName = $originalName.'-'.$count;
            $count++;
        }

        $role = Role::create([
            'business_id' => $request->user()->business_id,
            'name' => $roleName,
            'label' => $data['label'],
            'guard_name' => 'business',
            'is_default' => false,
        ]);

        $role->syncPermissions($data['permissions']);

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'role.created',
            description: "Menambahkan peran baru: {$role->label}",
            subject: $role,
            causer: $request->user(),
            properties: [
                'new' => [
                    'name' => $role->name,
                    'label' => $role->label,
                    'permissions' => $data['permissions'],
                ],
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Store a new role from a predefined template.
     */
    public function storeTemplate(Request $request, RoleProvisioningService $provisioningService)
    {
        $this->authorize('role.create');

        $request->validate([
            'template_key' => ['required', 'string'],
        ]);

        $template = RoleTemplateEnum::tryFrom($request->input('template_key'));
        if (! $template) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Template peran tidak valid.');
        }

        $provisioningService->applyTemplate($request->user()->business, $template->value);

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'role.template_applied',
            description: "Menerapkan template peran: {$template->label()}",
            subject: null,
            causer: $request->user(),
            properties: [
                'template_key' => $template->value,
                'template_label' => $template->label(),
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        // Check if role belongs to this business
        if ($role->business_id !== $request->user()->business_id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        // If trying to edit owner, abort
        if ($role->name === RoleEnum::OWNER->value) {
            abort(Response::HTTP_FORBIDDEN, 'Role Owner tidak dapat diubah izinnya.');
        }

        $before = [
            'label' => $role->label,
            'permissions' => $role->permissions()->pluck('name')->toArray(),
        ];

        if (! $role->is_default) {
            $role->update([
                'label' => $data['label'],
            ]);
        }

        $role->syncPermissions($data['permissions']);

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'role.updated',
            description: "Memperbarui hak akses peran: {$role->label}",
            subject: $role,
            causer: $request->user(),
            properties: [
                'old' => $before,
                'new' => [
                    'label' => $role->label,
                    'permissions' => $data['permissions'],
                ],
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        $this->authorize('role.delete');

        // Check if role belongs to this business
        if ($role->business_id !== $request->user()->business_id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // Anti-lockout guardrail
        if ($role->is_default) {
            abort(Response::HTTP_FORBIDDEN, 'Role bawaan tidak dapat dihapus.');
        }

        if ($role->users()->count() > 0) {
            abort(Response::HTTP_FORBIDDEN, 'Role masih digunakan oleh pengguna aktif.');
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'role.deleted',
            description: "Menghapus peran: {$role->label}",
            subject: $role,
            causer: $request->user()
        );

        $role->delete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }
}
