<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $query = Business::query()
            ->with(['type'])
            ->withCount(['outlets']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['name', 'status', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $businesses = $query->paginate(20)->withQueryString();

        return Inertia::render('Cockpit/Business/Index', [
            'businesses' => $businesses,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    public function show($id)
    {
        $business = Business::with(['type'])
            ->withCount(['outlets', 'users'])
            ->findOrFail($id);

        return Inertia::render('Cockpit/Business/Show', [
            'business' => $business,
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $business = Business::findOrFail($id);
        $oldStatus = $business->status;
        $newStatus = $request->input('status');

        if ($oldStatus !== $newStatus) {
            $business->update(['status' => $newStatus]);

            BusinessStatusLog::create([
                'business_id' => $business->id,
                'old_status' => $oldStatus ?? 'unknown',
                'new_status' => $newStatus,
                'changed_by' => Auth::guard('cockpit')->id(),
            ]);
        }

        return back()->with('success', 'Status merchant berhasil diperbarui.');
    }
}
