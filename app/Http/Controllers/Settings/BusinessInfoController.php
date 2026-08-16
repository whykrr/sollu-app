<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\SaveBusinessLogoRequest;
use App\Http\Requests\BusinessUpdateRequest;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessInfoController extends Controller
{
    public function index(Request $req): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_VIEW->value);

        $business = Auth::user()->business;

        return Inertia::render('Settings/Business/Detail', [
            'business' => $business,
        ]);
    }

    public function save(BusinessUpdateRequest $req)
    {
        $this->authorize(PermissionEnum::BUSINESS_UPDATE->value);

        $business_id = Auth::user()->business_id;

        /**
         * @var Business
         */
        $business = Business::findOrFail($business_id);
        $business->name = $req->validated('name');
        $business->email = $req->validated('email');
        $business->phone = $req->validated('phone');
        $business->owner_name = $req->validated('owner_name');
        $business->address = $req->validated('address');
        $business->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function saveLogo(SaveBusinessLogoRequest $request)
    {
        $business_id = Auth::user()->business_id;
        $business = Business::findOrFail($business_id);

        if (! $request->hasFile('logo')) {
            if ($business->logo && Storage::exists($business->logo)) {
                Storage::delete($business->logo);
            }
            $business->logo = null;
            $business->save();

            SummaryUser::cacheDelete();

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        }

        $path = $request->file('logo')->store('business/image');

        if ($business->logo && Storage::exists($business->logo)) {
            Storage::delete($business->logo);
        }

        $business->logo = $path;
        $business->save();

        SummaryUser::cacheDelete();

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
