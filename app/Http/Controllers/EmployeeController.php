<?php

namespace App\Http\Controllers;

use App\Constants\ResourceMessage;
use App\Enum\RoleEnum;
use App\Http\Requests\Employee\GetEmployeeRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role as ModelsRole;
use App\Models\User;
use App\Notifications\NewEmployee;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetEmployeeRequest $req, User $user = null)
    {
        $users = User::currentBusiness()
            ->selectedOutlet($req->get('outlet'))
            ->filters($req->safe()->only(['search', 'role', 'is_deleted']))
            ->sortable($req->validated('sort', 'created_at'), $req->validated('direction', 'desc'))
            ->with(['roles', 'outlets'])
            ->paginate($req->validated('perpage', 20))
            ->appends($req->query());

        if ($user) {
            $user->load(['roles:name', 'outlets:id']);
        }

        return inertia('Employee/Index', [
            'users'  => $users,
            'params' => $req->validated(),
            'roles'  => ModelsRole::get()->map(function ($row) {
                return [
                    'value' => $row->name,
                    'label' => RoleEnum::tryFrom($row->name)?->label() ?? $row->name,
                ];
            }),
            'user' => $user,
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
            $user = Auth::user()->business->users()->create([
                'name'              => $req['name'],
                'email'             => $req['email'],
                'phone'             => $req['phone'],
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

        return redirect()->route('employees.index')->with('success', ResourceMessage::CREATE_SUCCESS);
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

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    /**
     * Soft deletes the specified resource from storage.
     */
    public function delete(Request $req, User $user)
    {
        $user->deleteOrFail();

        return redirect()->back()->with('success', ResourceMessage::DELETE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function restore(Request $req, User $user)
    {
        $user->restore();

        return redirect()->back()->with('success', ResourceMessage::RESTORE_SUCCESS);
    }

    /**
     * Restore the specified resource to storage.
     */
    public function destroy(Request $req, User $user)
    {
        $user->forceDelete();

        return redirect()->back()->with('success', ResourceMessage::PURGE_SUCCESS);
    }
}
