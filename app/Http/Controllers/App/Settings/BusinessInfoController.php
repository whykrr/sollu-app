<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Business\SaveBusinessLogoRequest;
use App\Http\Requests\App\BusinessUpdateRequest;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessInfoController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger
    ) {}

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
        $before = [
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            'owner_name' => $business->owner_name,
            'address' => $business->address,
        ];

        $business->name = $req->validated('name');
        $business->email = $req->validated('email');
        $business->phone = $req->validated('phone');
        $business->owner_name = $req->validated('owner_name');
        $business->address = $req->validated('address');
        $business->save();

        $this->auditLogger->log(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'business.updated',
            description: 'Memperbarui profil informasi usaha',
            subject: $business,
            causer: Auth::user(),
            businessId: $business->id,
            properties: [
                'old' => $before,
                'new' => [
                    'name' => $business->name,
                    'email' => $business->email,
                    'phone' => $business->phone,
                    'owner_name' => $business->owner_name,
                    'address' => $business->address,
                ],
            ]
        );

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

            $this->auditLogger->log(
                module: AuditModuleEnum::SETTINGS->value,
                action: 'business.logo_removed',
                description: 'Menghapus logo usaha',
                subject: $business,
                causer: Auth::user(),
                businessId: $business->id
            );

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

        $this->auditLogger->log(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'business.logo_updated',
            description: 'Memperbarui logo usaha',
            subject: $business,
            causer: Auth::user(),
            businessId: $business->id
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }
}
