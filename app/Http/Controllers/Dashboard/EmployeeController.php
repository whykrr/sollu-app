<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Notifications\NewEmployee;
use App\ResourceMessage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as ModelsRole;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::whereMerchantId($request->user()->merchant_id)
            ->whereIsRootUser(false)
            ->filters($request->only(['search', 'outlet', 'role', 'status']))
            ->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))
            ->with(['roles:label', 'outlets'])
            ->paginate($request->get('perpage', 20))
            ->appends($request->query());


        return inertia('Dashboard/Employee/Index', [
            'users'  => $users,
            'params' => $request->all(),
            'roles'  => ModelsRole::whereNot('name', 'owner')->get()->map(function ($row) {
                return [
                    'value' => $row->name,
                    'label' => $row->label,
                ];
            }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Employee/Form', [
            'roles' => ModelsRole::whereNot('name', 'owner')
                ->get()
                ->map(function ($row) {
                    return [
                        'value' => $row->name,
                        'label' => $row->label,
                    ];
                }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(User $user)
    {
        return inertia('Dashboard/Employee/Form', [
            'returnTo' => url()->previous() != url()->current() ? url()->previous() : null,
            'user'     => $user->load(['roles:name', 'outlets:id']),
            'roles'    => ModelsRole::whereNot('name', 'owner')
                ->get()
                ->map(function ($row) {
                    return [
                        'value' => $row->name,
                        'label' => $row->label,
                    ];
                }),
        ]);
    }

    protected function checkEmailPhone($req, ?User $user = null)
    {
        /**
         * @var User $find
         */
        $find = User::where(function (Builder $builder) use ($req) {
            $builder->where('email', '=', $req['email']);
            $builder->orWhere('phone', '=', $req['phone']);
        });

        if ($user) {
            $find->where('id', '!=', $user->id);
        }
        $result = $find->first();

        if ($result !== null && $result->merchant_id !== Auth::user()->merchant_id) {
            if ($result->email === $req['email']) {
                throw ValidationException::withMessages(['email' => 'Sudah terdafar di merchant lain!']);
            } elseif (! empty($req['phone']) && $result->phone === $req['phone']) {
                throw ValidationException::withMessages(['phone' => 'Sudah terdafar di merchant lain!']);
            }
        } elseif ($result) {
            if ($result->email === $req['email']) {
                throw ValidationException::withMessages(['email' => 'Sudah terdafar!']);
            } elseif (! empty($req['phone']) && $result->phone === $req['phone']) {
                throw ValidationException::withMessages(['phone' => 'Sudah terdafar!']);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $req = $request->validated();

        $this->checkEmailPhone($req);

        $password_default = Str::random(10);

        DB::beginTransaction();
        try {
            /**
             * @var \App\Models\User $user
             */
            $user = Auth::user()->merchant->users()->create([
                'name'         => $req['name'],
                'email'        => $req['email'],
                'password'     => $password_default,
                'is_root_user' => false,
            ]);

            $user->assignRole($req['role']);
            $user->outlets()->attach($req['outlets']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $user->notify(new NewEmployee($password_default));

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $req = $request->validated();

        $this->checkEmailPhone($req, $user);

        DB::beginTransaction();

        try {
            $user->update($req);

            if (! $user->hasRole($req['role'])) {
                $user->syncRoles($req['role']);
            }
            $user->outlets()->sync($req['outlets']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->to($request->input('return_url') ?? route('dashboard.employees.index'))->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    /**
     * Soft deletes the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->deleteOrFail();

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::DELETE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function restore(User $user)
    {
        $user->restore();

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::RESTORE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function purge(User $user)
    {
        Gate::authorize('forceDelete', $user);

        $user->forceDelete();

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::PURGE_SUCCESS);
    }
}
