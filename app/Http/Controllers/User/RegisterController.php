<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Request;

class RegisterController extends Controller
{
    public function index(Request $request)
    {
        return inertia('User/Register');

    }
    public function store(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $merchant = Merchant::create([
                'name'               => $request->name,
                'owner_name'         => $request->owner_name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'address'            => $request->address,
                'already_free_trial' => true,
                'merchant_type_id'   => $request->merchant_type_id,
            ]);

            $merchant->outlets()->create([
                'name'           => $request->outlet_name,
                'address'        => $request->address,
                'status'         => 'active',
                'expired_at'     => Carbon::now()->addDays(15)->format('Y-m-d'),
                'is_main_outlet' => true,
            ]);

            $user = $merchant->users()->create([
                'name'         => $request->owner_name,
                'email'        => $request->email,
                'password'     => $request->password,
                'is_root_user' => true,
            ]);

            // assign user role
            $user->assignRole('owner');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('login')->with('success', 'Registration successful. Please verify your email before logging in.');
    }
}
