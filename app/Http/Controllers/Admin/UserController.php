<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        Gate::authorize('user', 'cms');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            ...$request->only(['search', 'order', 'by', 'status'])
        ];

        return inertia('User/Index', [
            'users' => User::filter($filters)->client()->paginate(10),
            'filters' => $filters
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('User/Input');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //set password default
        $request->merge([
            'password' => 'password'
        ]);

        $user = User::make($request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'role' => ['required'],
            'password' => ['required'],
        ]));

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User was created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return inertia('User/Input', [
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->validate(
            [
                'name' => ['required', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'role' => ['required'],
            ]
        ));

        return redirect()->route('admin.users.index')->with('success', 'User was updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->deleteOrFail();

        return redirect()->route('admin.users.index')->with('success', 'User was deleted!');
    }

    public function restore(User $user)
    {
        $user->restore();

        return redirect()->route('admin.users.index')->with('success', 'User was restored!');
    }

    public function permanentDelete(User $user)
    {
        $user->forceDelete();

        return redirect()->route('admin.users.index')->with('success', 'User was force deleted!');
    }
}
