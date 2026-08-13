<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Supplier\GetSupplierRequest;
use App\Http\Requests\Inventory\Supplier\StoreSupplierRequest;
use App\Http\Requests\Inventory\Supplier\UpdateSupplierRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetSupplierRequest $request)
    {
        $validated = $request->validated();

        $suppliers = Supplier::currentBusiness()
            ->filters($validated)
            ->with('inventoryItems:id,name') // include related items for display if needed
            ->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            }, function ($query) {
                $query->latest();
            })
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        return inertia('Inventory/Supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'is_active' => $validated['is_active'] ?? '',
            ],
        ]);
    }

    /**
     * API Endpoint to search inventory items for the supplier form.
     */
    public function searchItems(Request $request)
    {
        $search = $request->get('search');

        $items = InventoryItem::currentBusiness()
            ->when($search, function ($query, $search) {
                $query->whereLike('name', "%{$search}%");
            })
            ->select('id', 'name')
            ->limit(50)
            ->get();

        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['business_id'] = Auth::user()->business_id;

        $supplier = Supplier::create($validated);

        if (isset($validated['inventory_items'])) {
            $supplier->inventoryItems()->sync($validated['inventory_items']);
        }

        return redirect()->back()->with('success', 'Supplier berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, string $id)
    {
        $supplier = Supplier::currentBusiness()->findOrFail($id);
        $validated = $request->validated();

        $supplier->update($validated);

        if (isset($validated['inventory_items'])) {
            $supplier->inventoryItems()->sync($validated['inventory_items']);
        } else {
            $supplier->inventoryItems()->sync([]);
        }

        return redirect()->back()->with('success', 'Supplier berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::currentBusiness()->findOrFail($id);

        // Prevent deletion if there are active purchase orders (optional logic, but safe to implement)
        if ($supplier->purchaseOrders()->exists()) {
            $supplier->update(['is_active' => false]);

            return redirect()->back()->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat Purchase Order. Status telah dinonaktifkan.');
        }

        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier berhasil dihapus.');
    }
}
