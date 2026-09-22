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
use App\Jobs\Employee\ExportEmployeeJob;
use App\Jobs\Employee\ImportEmployeeJob;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use App\Services\App\Employee\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * Export employees to Excel in background queue.
     */
    public function export(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::USER_VIEW->value);

        $filters = $request->only(['search', 'role', 'outlet', 'is_deleted']);

        ExportEmployeeJob::dispatch($request->user(), $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }

    /**
     * Download template Excel for importing employees.
     */
    public function importTemplate(Request $request): BinaryFileResponse
    {
        $this->authorize(PermissionEnum::USER_CREATE->value);

        $businessId = $request->user()->business_id;

        $roles = Role::where('business_id', $businessId)
            ->get()
            ->map(fn (Role $r) => $r->label ?? $r->name)
            ->values();

        $outlets = Outlet::where('business_id', $businessId)
            ->get()
            ->pluck('name')
            ->values();

        $sampleRole = $roles->first() ?? 'Kasir';
        $sampleOutlet = $outlets->first() ?? 'Outlet Utama';

        $headers = [
            'Nama Lengkap',
            'Email',
            'Nomor Telepon',
            'PIN (6 Angka)',
            'Peran',
            'Outlet',
        ];

        $dummyData = [
            [
                'Ahmad Fauzi',
                'ahmad.fauzi@contoh.com',
                '081234567890',
                '123456',
                $sampleRole,
                $sampleOutlet,
            ],
            [
                'Siti Rahma',
                'siti.rahma@contoh.com',
                '081298765432',
                '654321',
                $sampleRole,
                $outlets->count() > 1 ? $outlets->take(2)->implode(', ') : $sampleOutlet,
            ],
        ];

        $export = new class($headers, $dummyData) implements FromArray, WithHeadings
        {
            public function __construct(
                private array $headers,
                private array $dummyData
            ) {}

            public function array(): array
            {
                return $this->dummyData;
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        return Excel::download($export, 'template_pegawai.xlsx');
    }

    /**
     * Import employees from uploaded Excel file in background queue.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::USER_CREATE->value);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file tidak boleh melebihi 10MB.',
        ]);

        $path = $request->file('file')->store('imports', 'local');

        ImportEmployeeJob::dispatch($request->user(), $path);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::IMPORT_PROCESSING
        );
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
