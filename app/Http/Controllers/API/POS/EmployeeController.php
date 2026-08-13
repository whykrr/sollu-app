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
}
