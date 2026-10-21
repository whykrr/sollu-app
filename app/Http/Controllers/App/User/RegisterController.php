<?php

namespace App\Http\Controllers\App\User;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Events\User\BusinessRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\User\RegisterRequest;
use App\Models\BusinessType;
use App\Models\SubscriptionPlan;
use App\Notifications\WelcomeUser;
use App\Services\App\User\RegisterBusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __construct(
        protected RegisterBusinessService $registerBusinessService
    ) {}

    public function index(Request $request)
    {
        $businessTypes = BusinessType::getAllCached()
            ->where('is_visible', true)
            ->values()
            ->map(function ($row) {
                return [
                    'value' => $row->id,
                    'label' => $row->name,
                    'code' => $row->code,
                ];
            });

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->with(['systemFeatures:id,code,name,module'])
            ->orderBy('price_per_outlet', 'asc')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'price_per_outlet' => (float) $plan->price_per_outlet,
                    'yearly_discount_percent' => (int) $plan->yearly_discount_percent,
                    'max_outlets' => $plan->max_outlets,
                    'is_unlimited_outlets' => $plan->isUnlimitedOutlets(),
                    'has_trial' => (bool) $plan->has_trial,
                    'trial_duration_days' => $plan->getTrialDays(),
                    'features' => $plan->systemFeatures->map(fn ($f) => [
                        'id' => $f->id,
                        'code' => $f->code,
                        'name' => $f->name,
                        'module' => $f->module,
                    ]),
                ];
            });

        $planParam = $request->query('plan');
        $trialParam = $request->query('trial');
        $cycleParam = $request->query('billing_cycle');

        $initialPlanCode = $planParam ? strtolower(trim((string) $planParam)) : null;
        $initialUseTrial = $trialParam !== null
            ? filter_var($trialParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $initialBillingCycle = in_array($cycleParam, ['monthly', 'yearly'], true) ? $cycleParam : 'monthly';

        return inertia('User/Register', [
            'business_types' => $businessTypes,
            'plans' => $plans,
            'initial_plan_code' => $initialPlanCode,
            'initial_use_trial' => $initialUseTrial,
            'initial_billing_cycle' => $initialBillingCycle,
        ]);
    }

    public function store(RegisterRequest $request)
    {
        $result = $this->registerBusinessService->execute($request->validated());

        Auth::guard('business')->login($result['user']);

        $result['user']->sendEmailVerificationNotification();
        $result['user']->notify(new WelcomeUser($result['user']));

        BusinessRegistered::dispatch($result['business'], $result['user'], $result['outlet']);

        if (! empty($result['invoice']) && $result['invoice']->total_amount > 0) {
            return redirect()->route('settings.billing.index', [
                'open_invoice' => $result['invoice']->invoice_number,
            ])->with(
                FlashDataVariable::SUCCESS->value,
                'Pendaftaran berhasil! Silakan selesaikan pembayaran tagihan awal untuk mengaktifkan paket langganan Anda.'
            );
        }

        return redirect()->route('overview')->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::REGISTER_SUCCESS
        );
    }
}
