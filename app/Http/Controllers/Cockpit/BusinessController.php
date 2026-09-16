<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessType;
use App\Services\Cockpit\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService
    ) {}

    /**
     * Search merchants for dropdowns/autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('query') ?? $request->input('search');

        $businesses = Business::query()
            ->select(['id', 'name', 'owner_name', 'email', 'phone', 'status'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', 'asc')
            ->limit(15)
            ->get();

        return response()->json($businesses);
    }

    /**
     * Display a listing of merchants with KPI metrics and filters.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
            'business_type_id' => (string) $request->input('business_type_id', ''),
            'subscription_status' => (string) $request->input('subscription_status', ''),
            'sort' => (string) $request->input('sort', 'created_at'),
            'direction' => (string) $request->input('direction', 'desc'),
        ];

        $businesses = $this->merchantService->getPaginatedMerchants($filters, 20);
        $metrics = $this->merchantService->getMetrics();
        $businessTypes = BusinessType::getAllCached();

        return Inertia::render('Cockpit/Business/Index', [
            'businesses' => $businesses,
            'metrics' => $metrics,
            'businessTypes' => $businessTypes,
            'filters' => $filters,
        ]);
    }

    /**
     * Display detailed merchant information including actual plan, outlets, and users.
     */
    public function show(string $id): JsonResponse
    {
        $detail = $this->merchantService->getMerchantDetail($id);

        return response()->json($detail);
    }

    /**
     * Toggle merchant status between active and suspended.
     */
    public function toggleStatus(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BusinessStatus::class)],
        ]);

        $this->merchantService->toggleStatus(
            $id,
            $validated['status'],
            (string) Auth::guard('cockpit')->id()
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Status merchant berhasil diperbarui.'
        );
    }

    /**
     * Impersonate merchant user from cockpit.
     */
    public function impersonate(Request $request, string $id, string $userId): RedirectResponse
    {
        $token = $this->merchantService->generateImpersonationToken(
            $id,
            $userId,
            (string) Auth::guard('cockpit')->id()
        );

        $appDomain = config('domain.app', 'app.sollu.test');
        if ($request->getPort() && ! in_array($request->getPort(), [80, 443]) && ! str_contains($appDomain, ':')) {
            $appDomain .= ':'.$request->getPort();
        }

        $url = "{$request->getScheme()}://{$appDomain}/impersonate/{$token}";

        return redirect()->away($url);
    }
}
