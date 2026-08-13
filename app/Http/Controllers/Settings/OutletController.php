<?php

namespace App\Http\Controllers\Settings;

use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\CreateOutletRequest;
use App\Http\Requests\Outlet\UpdateOutletRequest;
use App\Models\Outlet;
use App\Services\Outlet\CreateOutletService;
use App\Services\Outlet\ManageOutletStatusService;
use App\Services\Outlet\UpdateOutletService;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function __construct(
        protected CreateOutletService $createOutletService,
        protected UpdateOutletService $updateOutletService,
        protected ManageOutletStatusService $manageStatusService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req, ?Outlet $outlet = null)
    {
        $outlets = fn () => Outlet::currentBusiness()
            ->sortable($req->get('sort', 'created_at'), $req->get('direction', 'asc'))
            ->paginate($req->get('perpage', 20))
            ->appends($req->query());

        if ($outlet) {
            $outlet->load([
                'settings',
                'operationalHours',
                'devices' => fn ($q) => $q->withCount('tokens'),
                'auditLogs' => fn ($q) => $q->with('user')->latest()->take(50),
            ]);
        }

        $business = $req->user()->business;
        $maxOutlets = $business->maxOutletsAllowed();
        $currentOutletsCount = $business->outlets()->count();
        $subscription = $business->subscriptions()->with('plan')->where('status', 'active')->first();
        $isTrial = $business->trial_end_at ? \Carbon\Carbon::parse($business->trial_end_at)->isFuture() : false;

        return inertia('Settings/Outlet/Index', [
            'outlets' => $outlets,
            'params' => $req->all(),
            'outlet' => fn () => $outlet,
            'limit' => [
                'max' => $maxOutlets,
                'current' => $currentOutletsCount,
                'reached' => $currentOutletsCount >= $maxOutlets,
                'is_trial' => $isTrial,
            ],
            'subscription' => $subscription,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOutletRequest $req)
    {
        $result = $this->createOutletService->execute($req->validated(), $req->user());

        if (! empty($result['invoice'])) {
            return redirect()->route('settings.billing.invoices.show', $result['invoice']->invoice_number)
                ->with('success', 'Outlet berhasil dibuat. Silakan selesaikan pembayaran tagihan prorasi untuk mengaktifkan outlet.');
        }

        return redirect()->back()->with('success', ResourceMessage::CREATE_SUCCESS);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutletRequest $request, Outlet $outlet)
    {
        $this->updateOutletService->execute($outlet, $request->validated(), $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function disabled(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->toggleStatus($outlet, false, $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function enabled(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->toggleStatus($outlet, true, $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }

    public function destroy(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->delete($outlet, $request->user());

        return redirect()->route('settings.outlets.index')->with('success', ResourceMessage::DELETE_SUCCESS);
    }

    public function restore(Request $request, string $id)
    {
        $business = $request->user()->business;
        if ($business && $business->outlets()->count() >= $business->maxOutletsAllowed()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => ['Batas maksimum outlet untuk paket langganan Anda telah tercapai. Harap upgrade paket Anda untuk mengembalikan outlet ini.'],
            ]);
        }

        $this->manageStatusService->restore($id, $request->user());

        return redirect()->back()->with('success', ResourceMessage::UPDATE_SUCCESS);
    }
}
