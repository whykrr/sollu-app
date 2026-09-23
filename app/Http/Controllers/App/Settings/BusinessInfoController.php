<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SummaryUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Business\SaveBusinessLogoRequest;
use App\Http\Requests\App\BusinessUpdateRequest;
use App\Models\Business;
use App\Services\Core\ImageOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BusinessInfoController extends Controller
{
    public function __construct(
        protected ActivityLoggerInterface $auditLogger,
        protected ImageOptimizerService $imageOptimizerService
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

        SummaryUser::cacheDelete(Auth::id());

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
            $this->imageOptimizerService->delete($business->logo);
            $business->logo = null;
            $business->save();

            SummaryUser::cacheDelete(Auth::id());

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

        $oldLogo = $business->logo;
        $path = $this->imageOptimizerService->optimizeAndStore(
            $request->file('logo'),
            'business/image',
            'logo'
        );

        $this->imageOptimizerService->delete($oldLogo);

        $business->logo = $path;
        $business->save();

        SummaryUser::cacheDelete(Auth::id());

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
