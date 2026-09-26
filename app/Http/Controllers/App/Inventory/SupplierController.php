<?php

namespace App\Http\Controllers\App\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inventory\Supplier\GetSupplierRequest;
use App\Http\Requests\App\Inventory\Supplier\StoreSupplierRequest;
use App\Http\Requests\App\Inventory\Supplier\UpdateSupplierRequest;
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
            ->sortable($request->get('sort', 'created_at'), $request->get('direction', 'desc'))
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        return inertia('Inventory/Supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'is_active' => $validated['is_active'] ?? '',
                'sort' => $request->get('sort', 'created_at'),
                'direction' => $request->get('direction', 'desc'),
            ],
        ]);
    }

    public function show(Request $request, Supplier $supplier)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        return response()->json(
            $supplier->load('inventoryItems')
        );
    }

    /**
     * API Endpoint to search inventory items for the supplier form.
     */
    public function searchItems(Request $request)
    {
        $search = $request->get('search') ?: $request->get('query');

        $items = InventoryItem::query()
            ->where('inventory_items.business_id', Auth::user()->business_id)
            ->where('inventory_items.is_active', true)
            ->joinProductItem()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('inventory_items.name', "%{$search}%")
                        ->orWhereLike('product_items.sku', "%{$search}%")
                        ->orWhereLike('product_items.barcode', "%{$search}%");
                });
            })
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

        return redirect()
            ->back()
            ->with('success', 'Data supplier berhasil disimpan!');
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

        return redirect()
            ->back()
            ->with('success', 'Perubahan data supplier berhasil disimpan.');
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

            return redirect()
                ->back()
                ->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat Purchase Order. Status telah dinonaktifkan.');
        }

        $supplier->delete();

        return redirect()
            ->back()
            ->with('success', 'Data supplier berhasil dihapus.');
    }
}
