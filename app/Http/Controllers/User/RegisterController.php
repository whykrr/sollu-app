<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class RegisterController extends Controller
{
    public function index(Request $request)
    {
        $businessTypes = BusinessType::where('is_visible', true)->get()->map(function ($row) {
            return [
                'value' => $row->id,
                'label' => $row->name,
            ];
        });

        return inertia('User/Register', [
            'business_types' => $businessTypes,
        ]);

    }
    public function store(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $type = BusinessType::find($request->business_type_id);

            $business = Business::create([
                'name'             => $request->name,
                'owner_name'       => $request->owner_name,
                'email'            => $request->email,
                'phone'            => $request->phone,
                'status'           => 'active',
                'business_type_id' => $request->business_type_id,
                'settings'         => $type->default_settings,
                'trial_end_at'     => now()->addDays(15),
            ]);

            $outlet = $business->outlets()->create([
                'name'           => $request->outlet_name,
                'is_main_outlet' => true,
            ]);

            /**
             * @var User
             */
            $user = $business->users()->create([
                'name'         => $request->owner_name,
                'email'        => $request->email,
                'phone'        => $request->phone,
                'password'     => $request->password,
                'is_root_user' => true,
            ]);

            // assign user role
            $user->assignRole('owner');
            $user->outlets()->attach($outlet->id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        Auth::login($user);

        $user->sendEmailVerificationNotification();
        $user->notify(new WelcomeUser($user));

        return redirect()->route('overview')->with('success', 'Pendaftaran Berhasil!, Cek email Anda untuk verifikasi');
    }
}
