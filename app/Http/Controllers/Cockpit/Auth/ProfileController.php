<?php

namespace App\Http\Controllers\Cockpit\Auth;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\Auth\ProfilePasswordUpdateRequest;
use App\Http\Requests\Cockpit\Auth\ProfileUpdateRequest;
use App\Models\CockpitUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Update the authenticated cockpit user's profile name.
     */
    public function updateProfile(ProfileUpdateRequest $request)
    {
        /** @var CockpitUser $user */
        $user = Auth::guard('cockpit')->user();

        $user->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    /**
     * Update the authenticated cockpit user's password.
     */
    public function updatePassword(ProfilePasswordUpdateRequest $request)
    {
        /** @var CockpitUser $user */
        $user = Auth::guard('cockpit')->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini tidak valid!',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->validated('new_password')),
        ]);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
