<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\AuthorizationMessage;
use App\Constants\ResourceMessage;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantUpdateRequest;
use App\Models\Merchant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MerchantInfoController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can('merchant.info')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $merchant = Auth::user()->merchant;

        return inertia('Merchant/Detail/Index', [
            'merchant' => $merchant,
        ]);
    }

    public function save(MerchantUpdateRequest $req)
    {
        if (! Auth::user()->can('merchant.info')) {
            throw new AuthorizationException(AuthorizationMessage::EDIT_DATA_NOT_ALLOWED);
        }

        $merchant_id = Auth::user()->merchant_id;

        /**
         * @var Merchant
         */
        $merchant             = Merchant::find($merchant_id);
        $merchant->name       = $req->validated('name');
        $merchant->email      = $req->validated('email');
        $merchant->phone      = $req->validated('phone');
        $merchant->owner_name = $req->validated('owner_name');
        $merchant->address    = $req->validated('address');
        $merchant->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function saveLogo(Request $request)
    {
        if (! Auth::user()->can('merchant.info')) {
            throw new AuthorizationException(AuthorizationMessage::EDIT_DATA_NOT_ALLOWED);
        }

        $merchant_id = Auth::user()->merchant_id;

        $request->validate([
            'logo' => ['required', 'image'], // validasi file gambar
        ]);

        $path = $request->file('logo')->store('merchant/image');

        $merchant = Merchant::find($merchant_id);

        if ($merchant->logo && Storage::exists($merchant->logo)) {
            Storage::delete($merchant->logo);
        }

        $merchant->logo = $path;
        $merchant->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);

    }
}
