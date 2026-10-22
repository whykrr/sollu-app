<?php

namespace App\Http\Controllers\App\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Master\StoreModifierGroupRequest;
use App\Http\Requests\App\Master\UpdateModifierGroupRequest;
use App\Models\Master\ModifierGroup;
use App\Services\App\Master\ModifierService;
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
            ->withCount(['options', 'products'])
            ->filters($request->only('search'))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master/Product/Modifier/Index', [
            'modifiers' => $modifiers,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Request $request, ModifierGroup $modifier)
    {
        if ($modifier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        return response()->json($modifier->load('options'));
    }

    public function store(StoreModifierGroupRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = $request->user()->business_id;

        $this->modifierService->createGroup($data);

        return redirect()->back()->with('success', 'Modifier berhasil dibuat');
    }

    public function update(UpdateModifierGroupRequest $request, ModifierGroup $modifier)
    {
        if ($modifier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $data = $request->validated();
        $this->modifierService->updateGroup($modifier, $data);

        return redirect()->back()->with('success', 'Modifier berhasil diupdate');
    }

    public function destroy(Request $request, ModifierGroup $modifier)
    {
        if ($modifier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        if ($modifier->products()->exists()) {
            return redirect()->back()->with('error', 'Modifier sedang digunakan oleh produk aktif.');
        }

        $this->modifierService->deleteGroup($modifier);

        return redirect()->back()->with('success', 'Modifier berhasil dihapus');
    }
}
