<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Sales\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('transaction.view');

        $filters = $request->only(['search', 'status', 'start_date', 'end_date', 'sort', 'direction']);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        $shifts = Shift::with(['user', 'outlet'])
            ->filters($filters)
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transaction/Shift/Index', [
            'shifts' => $shifts,
            'filters' => $filters,
        ]);
    }

    public function show(Shift $shift)
    {
        $this->authorize('transaction.view');

        $shift->load([
            'user',
            'outlet',
            'cashLogs',
        ]);

        return Inertia::render('Transaction/Shift/Show', [
            'shift' => $shift,
        ]);
    }
}
