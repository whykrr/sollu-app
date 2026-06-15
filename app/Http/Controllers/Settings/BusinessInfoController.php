<?php

namespace App\Http\Controllers\Settings;

use App\Constants\AuthorizationMessage;
use App\Constants\ResourceMessage;
use App\Enum\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessUpdateRequest;
use App\Models\Business;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BusinessInfoController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can(PermissionEnum::BUSINESS_VIEW->value)) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $business = Auth::user()->business;

        return inertia('Settings/Business/Detail', [
            'business' => $business,
        ]);
    }

    public function save(BusinessUpdateRequest $req)
    {
        if (! Auth::user()->can(PermissionEnum::BUSINESS_UPDATE->value)) {
            throw new AuthorizationException(AuthorizationMessage::EDIT_DATA_NOT_ALLOWED);
        }

        $business_id = Auth::user()->business_id;

        /**
         * @var Business
         */
        $business             = Business::find($business_id);
        $business->name       = $req->validated('name');
        $business->email      = $req->validated('email');
        $business->phone      = $req->validated('phone');
        $business->owner_name = $req->validated('owner_name');
        $business->address    = $req->validated('address');
        $business->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function saveLogo(Request $request)
    {
        if (! Auth::user()->can(PermissionEnum::BUSINESS_UPDATE->value)) {
            throw new AuthorizationException(AuthorizationMessage::EDIT_DATA_NOT_ALLOWED);
        }

        $business_id = Auth::user()->business_id;
        $business    = Business::find($business_id);

        if (! $request->hasFile('logo')) {
            $business->logo = null;

            SummaryUser::cacheDelete();
            $business->save();

            return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);

        }

        $path = $request->file('logo')->store('business/image');


        if ($business->logo && Storage::exists($business->logo)) {
            Storage::delete($business->logo);
        }

        $business->logo = $path;
        $business->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);

    }
}
