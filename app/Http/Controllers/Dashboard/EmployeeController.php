<?php

namespace App\Http\Controllers\Dashboard;

use App\Constants\AuthorizationMessage;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Notifications\NewEmployee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as ModelsRole;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        if (! $req->user()->can('user.view')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $users = User::currentMerchant()
            ->whereIsRootUser(false)
            ->selectedOutlet($req->get('outlet'))
            ->filters($req->only(['search', 'role', 'status']))
            ->sortable($req->get('sort', 'updated_at'), $req->get('direction', 'desc'))
            ->with(['roles:label', 'outlets'])
            ->paginate($req->get('perpage', 20))
            ->appends($req->query());


        return inertia('Dashboard/Employee/Index', [
            'users'  => $users,
            'params' => $req->all(),
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
    public function create(Request $req)
    {
        if (! $req->user()->can('user.create')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

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
    public function show(Request $req, User $user)
    {
        if (! $req->user()->can('user.update')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        } elseif ($req->user()->id === $user->id) {
            throw new AuthorizationException(AuthorizationMessage::EDIT_DATA_NOT_ALLOWED);
        }

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
                throw ValidationException::withMessages(['email' => 'Sudah terdaftar di merchant lain!']);
            } elseif (! empty($req['phone']) && $result->phone === $req['phone']) {
                throw ValidationException::withMessages(['phone' => 'Sudah terdaftar di merchant lain!']);
            }
        } elseif ($result) {
            if ($result->email === $req['email']) {
                throw ValidationException::withMessages(['email' => 'Sudah terdaftar!']);
            } elseif (! empty($req['phone']) && $result->phone === $req['phone']) {
                throw ValidationException::withMessages(['phone' => 'Sudah terdaftar!']);
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
                'name'              => $req['name'],
                'email'             => $req['email'],
                'password'          => $password_default,
                'is_root_user'      => false,
                'email_verified_at' => Carbon::now(),
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
    public function destroy(Request $req, User $user)
    {
        if (! $req->user()->can('user.delete')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        } elseif ($req->user()->id === $user->id) {
            throw new AuthorizationException(AuthorizationMessage::DELETE_DATA_NOT_ALLOWED);
        }

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
    public function purge(Request $req, User $user)
    {
        if (! $req->user()->can('user.delete')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        } elseif ($req->user()->id === $user->id) {
            throw new AuthorizationException(AuthorizationMessage::DELETE_DATA_NOT_ALLOWED);
        }

        $user->forceDelete();

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::PURGE_SUCCESS);
    }
}
