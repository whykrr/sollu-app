<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewEmployee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeService
{
    /**
     * Create a new employee.
     *
     *
     * @throws \Exception
     */
    public function create(array $data): User
    {
        $password_default = Str::random(10);

        DB::beginTransaction();
        try {
            /**
             * @var \App\Models\User $user
             */
            $user = Auth::user()->business->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $password_default,
                'pin' => $data['pin'] ?? null,
                'is_root_user' => false,
                'email_verified_at' => Carbon::now(),
            ]);

            $user->assignRole($data['role']);
            $user->outlets()->attach($data['outlets']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $user->notify(new NewEmployee($password_default));

        return $user;
    }

    /**
     * Update an existing employee.
     *
     *
     * @throws \Exception
     */
    public function update(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            if (empty($data['pin'])) {
                unset($data['pin']);
            }

            $user->update($data);

            if (! $user->is_root_user) {
                if (! $user->hasRole($data['role'])) {
                    $user->syncRoles($data['role']);
                }
                $user->outlets()->sync($data['outlets']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    /**
     * Soft delete an employee.
     *
     *
     * @throws \Throwable
     */
    public function delete(User $user): void
    {
        $user->deleteOrFail();
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(User $user): void
    {
        $user->restore();
    }

    /**
     * Force delete an employee.
     */
    public function destroy(User $user): void
    {
        $user->forceDelete();
    }
}
