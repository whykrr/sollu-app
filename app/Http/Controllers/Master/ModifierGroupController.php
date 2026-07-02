<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ModifierGroup;
use App\Services\Master\ModifierService;
use App\Http\Requests\Master\StoreModifierGroupRequest;
use App\Http\Requests\Master\UpdateModifierGroupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModifierGroupController extends Controller
{
    private ModifierService $modifierService;

    public function __construct(ModifierService $modifierService)
    {
        $this->modifierService = $modifierService;
    }

    public function index(Request $request)
    {
        $modifiers = ModifierGroup::currentBusiness()
            ->with('options')
            ->withCount('products')
            ->filters($request->only('search'))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master/Product/Modifier/Index', [
            'modifiers' => $modifiers,
            'filters'   => $request->only('search'),
        ]);
    }

    public function store(StoreModifierGroupRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = auth()->user()->business_id ?? \App\Models\Business::first()->id;

        $this->modifierService->createGroup($data);

        return redirect()->back()->with('success', 'Modifier berhasil dibuat');
    }

    public function update(UpdateModifierGroupRequest $request, ModifierGroup $modifier)
    {
        $data = $request->validated();
        $this->modifierService->updateGroup($modifier, $data);

        return redirect()->back()->with('success', 'Modifier berhasil diupdate');
    }

    public function destroy(ModifierGroup $modifier)
    {
        if ($modifier->products()->exists()) {
            return redirect()->back()->with('error', 'Modifier sedang digunakan oleh produk aktif.');
        }
        
        $this->modifierService->deleteGroup($modifier);
        
        return redirect()->back()->with('success', 'Modifier berhasil dihapus');
    }
}
