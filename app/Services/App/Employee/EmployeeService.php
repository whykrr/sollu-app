<?php

namespace App\Services\App\Employee;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Models\User;
use App\Notifications\NewEmployee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmployeeService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    /**
     * Create a new employee.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function create(array $data): User
    {
        $password_default = Str::random(10);

        DB::beginTransaction();
        try {
            /** @var \App\Models\User $user */
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

            $this->auditLogger->log(
                module: AuditModuleEnum::EMPLOYEES->value,
                action: 'employee.created',
                description: "Menambahkan staf baru: {$user->name}",
                subject: $user,
                causer: Auth::user(),
                properties: [
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $data['role'],
                    ],
                ]
            );

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
     * @param  array<string, mixed>  $data
     *
     * @throws \Exception
     */
    public function update(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            if (empty($data['pin'])) {
                unset($data['pin']);
            }

            $user->update($data);

            if (! $user->is_root_user) {
                if (isset($data['role']) && ! $user->hasRole($data['role'])) {
                    $user->syncRoles($data['role']);
                }
                if (isset($data['outlets'])) {
                    $user->outlets()->sync($data['outlets']);
                }
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::EMPLOYEES->value,
                action: 'employee.updated',
                description: "Memperbarui data staf: {$user->name}",
                subject: $user,
                causer: Auth::user(),
                properties: [
                    'old' => $before,
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                ]
            );

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
     * @throws \Throwable
     */
    public function delete(User $user): void
    {
        if ($user->is_root_user) {
            throw new InvalidArgumentException('Akun pemilik usaha (Owner) tidak dapat dihapus.');
        }

        if (Auth::id() && $user->id === Auth::id()) {
            throw new InvalidArgumentException('Kamu tidak dapat menghapus akunmu sendiri.');
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.deleted',
            description: "Memindahkan akun staf ke sampah: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );

        $user->deleteOrFail();
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(User $user): void
    {
        $user->restore();

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.restored',
            description: "Memulihkan akun staf: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );
    }

    /**
     * Force delete an employee permanently.
     */
    public function destroy(User $user): void
    {
        if ($user->is_root_user) {
            throw new InvalidArgumentException('Akun pemilik usaha (Owner) tidak dapat dihapus.');
        }

        if (Auth::id() && $user->id === Auth::id()) {
            throw new InvalidArgumentException('Kamu tidak dapat menghapus akunmu sendiri.');
        }

        $this->auditLogger->log(
            module: AuditModuleEnum::EMPLOYEES->value,
            action: 'employee.destroyed',
            description: "Menghapus permanen akun staf: {$user->name}",
            subject: $user,
            causer: Auth::user()
        );

        $user->forceDelete();
    }
}
