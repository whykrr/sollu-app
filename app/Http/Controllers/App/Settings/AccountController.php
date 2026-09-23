<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\User\AccountChangePasswordRequest;
use App\Http\Requests\App\User\AccountUpdateRequest;
use App\Http\Requests\App\User\ChangePhotoRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

    public function index(Request $req)
    {
        $user = User::find(Auth::id());

        return inertia('Settings/Account/Detail', [
            'profile' => $user,
        ]);
    }

    public function save(AccountUpdateRequest $req)
    {
        $user = Auth::user();
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        /**
         * @var User
         */
        $user->name = $req->validated('name');
        $user->email = $req->validated('email');
        $user->phone = $req->validated('phone');
        $user->save();

        Cache::delete("auth:user:{$user->id}:info");
        SummaryUser::cacheDelete($user->id);

        $this->auditLogger->log(
            module: AuditModuleEnum::AUTH->value,
            action: 'account.profile_updated',
            description: 'Memperbarui informasi profil akun',
            subject: $user,
            causer: $user,
            properties: [
                'old' => $before,
                'new' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ]
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function changePassword(AccountChangePasswordRequest $req)
    {
        $user = User::find(Auth::id());

        // check current password
        if (! password_verify($req->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini tidak valid!',
            ]);
        }

        /**
         * @var User
         */
        $user->password = $req->validated('new_password');
        $user->save();

        $this->auditLogger->log(
            module: AuditModuleEnum::AUTH->value,
            action: 'account.password_changed',
            description: 'Mengubah kata sandi akun',
            subject: $user,
            causer: $user
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function savePhoto(ChangePhotoRequest $request)
    {
        $user = User::find(Auth::id());

        $path = $request->file('photo')->store('user/photo');

        if ($user->photo && Storage::exists($user->photo)) {
            Storage::delete($user->photo);
        }

        $user->photo = $path;
        $user->save();

        Cache::delete("auth:user:{$user->id}:info");
        SummaryUser::cacheDelete($user->id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function removePhoto(Request $req)
    {
        $user = User::find(Auth::id());

        if ($user->photo && Storage::exists($user->photo)) {
            Storage::delete($user->photo);
        }

        $user->photo = null;
        $user->save();

        Cache::delete("auth:user:{$user->id}:info");
        SummaryUser::cacheDelete($user->id);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }
}
