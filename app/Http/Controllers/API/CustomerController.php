<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->input('query');

        $customers = \App\Models\Master\Customer::currentBusiness()
            ->select('id', 'name', 'phone')
            ->when($search, function ($query, $search) {
                $query->whereLike('name', "%{$search}%")
                    ->orWhereLike('phone', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($customer) {
                return [
                    'value' => $customer->id,
                    'name' => $customer->name.($customer->phone ? " ({$customer->phone})" : ''),
                ];
            });

        return response()->json($customers);
    }
}
