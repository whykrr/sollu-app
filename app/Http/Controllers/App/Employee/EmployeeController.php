<?php

namespace App\Http\Controllers\App\Employee;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Employee\GetEmployeeRequest;
use App\Http\Requests\App\Employee\StoreEmployeeRequest;
use App\Http\Requests\App\Employee\UpdateEmployeeRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\App\Employee\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeService $employeeService) {}

    /**
     * Display a listing of the employees.
     */
    public function index(GetEmployeeRequest $req): Response
    {
        $filters = $req->safe()->only(['search', 'role', 'outlet', 'is_deleted']);
        $sort = $req->validated('sort', 'created_at');
        $direction = $req->validated('direction', 'desc');
        $perPage = (int) $req->validated('perpage', 20);

        $users = User::currentBusiness()
            ->filters($filters)
            ->sortable($sort, $direction)
            ->with(['roles:id,name,label', 'outlets:id,name'])
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::where('business_id', $req->user()->business_id)
            ->select('id', 'name', 'label')
            ->get()
            ->map(function ($row) {
                return [
                    'value' => $row->name,
                    'label' => $row->label ?? (RoleEnum::tryFrom($row->name)?->label() ?? $row->name),
                ];
            });

        return Inertia::render('Employee/Index', [
            'users' => $users,
            'params' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'outlet' => $filters['outlet'] ?? '',
                'is_deleted' => $filters['is_deleted'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'perpage' => $perPage,
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Display the specified employee on-demand.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize(PermissionEnum::USER_VIEW->value);

        if ($user->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        $user->load(['roles:id,name,label', 'outlets:id,name']);

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->create($request->validated());

        return redirect()->route('employees.index')->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, User $user): RedirectResponse
    {
        if ($user->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        $this->employeeService->update($user, $request->validated());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    /**
     * Soft delete the specified employee from storage.
     */
    public function delete(Request $req, User $user): RedirectResponse
    {
        $this->authorize(PermissionEnum::USER_DELETE->value);

        if ($user->business_id !== $req->user()?->business_id) {
            abort(403);
        }

        try {
            $this->employeeService->delete($user);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    /**
     * Restore the specified employee to storage.
     */
    public function restore(Request $req, User $user): RedirectResponse
    {
        $this->authorize(PermissionEnum::USER_DELETE->value);

        if ($user->business_id !== $req->user()?->business_id) {
            abort(403);
        }

        $this->employeeService->restore($user);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::RESTORE_SUCCESS
        );
    }

    /**
     * Permanently destroy the specified employee from storage.
     */
    public function destroy(Request $req, User $user): RedirectResponse
    {
        $this->authorize(PermissionEnum::USER_DELETE->value);

        if ($user->business_id !== $req->user()?->business_id) {
            abort(403);
        }

        try {
            $this->employeeService->destroy($user);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::PURGE_SUCCESS
        );
    }
}
