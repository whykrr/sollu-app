<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('query') ?: $request->input('q') ?: $request->input('search');
        $limit = min((int) $request->input('limit', 10), 50);

        $customers = Customer::currentBusiness()
            ->select('id', 'name', 'phone')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('name', "%{$search}%")
                        ->orWhereLike('phone', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                $formattedName = $customer->name.($customer->phone ? " ({$customer->phone})" : '');

                return [
                    'id' => $customer->id,
                    'value' => $customer->id,
                    'name' => $formattedName,
                    'label' => $formattedName,
                    'phone' => $customer->phone,
                ];
            });

        return response()->json($customers);
    }
}
