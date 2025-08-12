<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\ResourceMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = Auth::user()->merchant
        ->users()->with(['roles:label', 'outlets'])
        ->paginate(10);

        return inertia('Dashboard/Employee/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Employee/Input');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(User $user)
    {
        $users = Auth::user()->merchant
          ->users()->with(['roles:label', 'outlets'])
          ->paginate(10);

        return inertia('Dashboard/Employee/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $req = $request->validated();

        /**
         * @var User $findEmail
         */
        $findEmail = User::whereEmail($req['email'])->first();
        if ($findEmail !== null && $findEmail->merchant_id !== Auth::user()->merchant_id) {
            throw ValidationException::withMessages([
                'email_other_merchant' => 'Email already registered in other merchant!',
            ]);
        } elseif ($findEmail) {
            throw ValidationException::withMessages([
                'email' => 'Email already registered!',
            ]);
        }

        DB::beginTransaction();
        try {
            /**
             * @var \App\Models\User $user
             */
            $user = Auth::user()->merchant->users()->create([
                'name'         => $req['name'],
                'email'        => $req['email'],
                'password'     => Str::random(10),
                'is_root_user' => false,
            ]);

            $user->assignRole($req['role']);
            $user->outlets()->attach($req['outlet_ids']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('users.index')->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return inertia('Dashboard/Employee/Form', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $req = $request->validated();

        DB::beginTransaction();

        try {
            $user->update($req);

            if (! $user->hasRole($req['role'])) {
                $user->syncRoles($req['role']);
            }
            $user->outlets()->sync($req['outlet_ids']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('dashboard.employees.index')->with('success', ResourceMessage::UPDATE_SUCCESS);
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
