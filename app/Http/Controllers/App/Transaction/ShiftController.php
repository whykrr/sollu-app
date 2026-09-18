<?php

namespace App\Http\Controllers\App\Transaction;

use App\Constants\AuthorizationMessage;
use App\Enums\DatePresetEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Transaction\Shift\GetShiftRequest;
use App\Http\Resources\Transaction\ShiftResource;
use App\Models\Sales\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(GetShiftRequest $request)
    {
        $this->authorize('transaction.view');

        $filters = $request->validated();

        $dateRange = DatePresetEnum::resolveRange(
            $request->validated('preset'),
            $request->validated('start_date'),
            $request->validated('end_date')
        );
        $filters['start_date'] = $dateRange['start_date'];
        $filters['end_date'] = $dateRange['end_date'];
        $filters['preset'] = $dateRange['preset'];

        $shifts = Shift::with(['user:id,name', 'outlet:id,name'])
            ->filters($filters)
            ->sortable($request->validated('sort', 'created_at'), $request->validated('direction', 'desc'))
            ->paginate($request->validated('perpage', 15))
            ->appends($request->query());

        return Inertia::render('Transaction/Shift/Index', [
            'shifts' => $shifts,
            'filters' => $filters,
        ]);
    }

    public function show(Shift $shift, Request $request)
    {
        $this->authorize('transaction.view');

        $user = Auth::user();
        if ($shift->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $shift->load([
            'user:id,name,email',
            'outlet:id,name',
            'cashLogs',
        ]);

        if ($request->wantsJson()) {
            return new ShiftResource($shift);
        }

        return Inertia::render('Transaction/Shift/Show', [
            'shift' => $shift,
        ]);
    }
}
