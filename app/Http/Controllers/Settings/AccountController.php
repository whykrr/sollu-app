<?php

namespace App\Http\Controllers\Settings;

use App\Constants\ResourceMessage;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AccountChangePasswordRequest;
use App\Http\Requests\User\AccountUpdateRequest;
use App\Http\Requests\User\ChangePhotoRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
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

        /**
         * @var User
         */
        $user->name = $req->validated('name');
        $user->email = $req->validated('email');
        $user->phone = $req->validated('phone');
        $user->save();

        SummaryUser::cacheDelete();
        Cache::delete("auth:user:{$user->id}:info");

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
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

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
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

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);

    }

    public function removePhoto(Request $req)
    {
        $user = User::find(Auth::id());

        if ($user->photo && Storage::exists($user->photo)) {
            Storage::delete($user->photo);
        }

        $user->photo = null;
        $user->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::DELETE_SUCCESS);
    }
}
