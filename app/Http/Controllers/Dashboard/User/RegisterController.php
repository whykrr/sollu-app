<?php

namespace App\Http\Controllers\Dashboard\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Models\Merchant;
use App\Models\MerchantType;
use App\Notifications\WelcomeUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class RegisterController extends Controller
{
    public function index(Request $request)
    {
        $merchantTypes = cache()->remember('merchant-types', (60 * 60), function () {
            return MerchantType::all();
        });

        return inertia('Dashboard/User/Register', [
            'merchant_types' => $merchantTypes,
        ]);

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
                'already_free_trial' => true,
                'merchant_type_id'   => $request->merchant_type_id,
            ]);

            $merchant->outlets()->create([
                'name'           => $request->outlet_name,
                'status'         => 'active',
                'expired_at'     => Carbon::now()->addDays(15)->format('Y-m-d'),
                'is_main_outlet' => true,
            ]);

            $user = $merchant->users()->create([
                'name'         => $request->owner_name,
                'email'        => $request->email,
                'phone'        => $request->phone,
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

        Auth::login($user);

        $user->sendEmailVerificationNotification();
        $user->notify(new WelcomeUser($user));

        return redirect()->route('dashboard.overview')->with('success', 'Pendaftaran Berhasil!, Cek email Anda untuk verifikasi');
    }
}
