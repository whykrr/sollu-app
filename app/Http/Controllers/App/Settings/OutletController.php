<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Outlet\CreateOutletRequest;
use App\Http\Requests\App\Outlet\UpdateOutletRequest;
use App\Models\Outlet;
use App\Services\App\Outlet\CreateOutletService;
use App\Services\App\Outlet\ManageOutletStatusService;
use App\Services\App\Outlet\UpdateOutletService;
use App\Services\App\Subscription\BillingEngine;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function __construct(
        protected CreateOutletService $createOutletService,
        protected UpdateOutletService $updateOutletService,
        protected ManageOutletStatusService $manageStatusService,
        protected BillingEngine $billingEngine
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req, ?Outlet $outlet = null)
    {
        $sort = $req->filled('sort') ? $req->get('sort') : 'created_at';
        $direction = $req->filled('direction') ? $req->get('direction') : 'asc';

        $outlets = fn () => Outlet::currentBusiness()
            ->when($req->get('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->sortable($sort, $direction)
            ->paginate($req->get('perpage', 20))
            ->appends($req->query());

        $business = $req->user()->business;
        $subscription = $business->subscriptions()->with('plan')->where('status', 'active')->first();

        $proratedAmount = $subscription ? $this->billingEngine->calculateProratedCost($subscription) : 0;

        return inertia('Settings/Outlet/Index', [
            'outlets' => $outlets,
            'params' => array_merge(['sort' => $sort, 'direction' => $direction], $req->all()),
            'outlet' => fn () => $outlet,
            'subscription' => $subscription,
            'proratedAmount' => $proratedAmount,
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
                ->with(FlashDataVariable::SUCCESS->value, 'Outlet berhasil dibuat. Silakan selesaikan pembayaran tagihan prorasi untuk mengaktifkan outlet.');
        }

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutletRequest $request, Outlet $outlet)
    {
        $this->updateOutletService->execute($outlet, $request->validated(), $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function disabled(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->toggleStatus($outlet, false, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function enabled(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->toggleStatus($outlet, true, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function destroy(Request $request, Outlet $outlet)
    {
        $this->manageStatusService->delete($outlet, $request->user());

        return redirect()->route('settings.outlets.index')->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function restore(Request $request, string $id)
    {
        $this->manageStatusService->restore($id, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function setMain(Request $request, Outlet $outlet)
    {
        $business = $request->user()->business;
        if ($outlet->business_id !== $business?->id) {
            abort(403);
        }

        $this->manageStatusService->setMainOutlet($outlet, $request->user());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Outlet utama berhasil diubah.'
        );
    }
}
