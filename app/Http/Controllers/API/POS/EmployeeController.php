<?php

namespace App\Http\Controllers\API\POS;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // $request->user() is the OutletDevice for POS APIs protected by pos.device middleware
        $device = $request->user();

        $device->load('outlet');
        $outlet = $device->outlet;

        if (! $outlet) {
            return $this->errorResponse('Outlet not found for this device.', [], 404);
        }

        // Get all users associated with this outlet
        $employees = $outlet->users()
            ->with(['roles:id,name'])
            ->select('users.id', 'users.name', 'users.email', 'users.pin', 'users.photo')
            ->get()
            ->map(function ($user) {
                $roleName = $user->roles->first()?->name;
                $roleEnum = $roleName ? RoleEnum::tryFrom($roleName) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pin' => $user->pin,
                    'photo' => $user->photo,
                    'role' => $roleEnum?->label() ?? ($roleName ?? 'Kasir'),
                ];
            });

        return $this->successResponse($employees, 'Data karyawan berhasil diambil.');
    }

    public function updatePin(Request $request)
    {
        $device = $request->user();
        $device->load('outlet');
        $outlet = $device->outlet;

        if (! $outlet) {
            return $this->errorResponse('Outlet tidak ditemukan untuk perangkat ini.', [], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'current_pin' => ['required', 'string', 'digits:6'],
            'pin' => ['required', 'string', 'digits:6'],
            'pin_confirmation' => ['required', 'string', 'same:pin'],
        ], [
            'user_id.required' => 'ID pengguna wajib diisi.',
            'user_id.exists' => 'Pengguna tidak ditemukan.',
            'current_pin.required' => 'PIN saat ini wajib diisi.',
            'current_pin.digits' => 'PIN saat ini harus berupa 6 digit angka.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus berupa 6 digit angka.',
            'pin_confirmation.required' => 'Konfirmasi PIN baru wajib diisi.',
            'pin_confirmation.same' => 'Konfirmasi PIN baru tidak sesuai.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        // Verify user belongs to this outlet
        $user = $outlet->users()->where('users.id', $request->user_id)->first();
        if (! $user) {
            return $this->errorResponse('Karyawan tidak terdaftar pada outlet ini.', [], 403);
        }

        // Check if current PIN matches
        if (! \Illuminate\Support\Facades\Hash::check($request->current_pin, $user->pin)) {
            return $this->errorResponse('PIN saat ini yang Anda masukkan salah.', [], 422);
        }

        // Update PIN (automatically hashed by User model cast)
        $user->update([
            'pin' => $request->pin,
        ]);

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'pin' => $user->fresh()->pin,
        ], 'PIN berhasil diperbarui.');
    }
}
