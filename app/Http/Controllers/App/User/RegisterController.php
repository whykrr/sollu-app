<?php

namespace App\Http\Controllers\App\User;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\User\RegisterRequest;
use App\Models\BusinessType;
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
        $businessTypes = BusinessType::where('is_visible', true)->get()->map(function ($row) {
            return [
                'value' => $row->id,
                'label' => $row->name,
            ];
        });

        return inertia('User/Register', [
            'business_types' => $businessTypes,
        ]);
    }

    public function store(RegisterRequest $request)
    {
        $result = $this->registerBusinessService->execute($request->validated());

        Auth::guard('business')->login($result['user']);

        $result['user']->sendEmailVerificationNotification();
        $result['user']->notify(new WelcomeUser($result['user']));

        \App\Events\User\BusinessRegistered::dispatch($result['business'], $result['user'], $result['outlet']);

        return redirect()->route('overview')->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::REGISTER_SUCCESS
        );
    }
}
