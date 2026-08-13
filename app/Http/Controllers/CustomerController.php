<?php

namespace App\Http\Controllers;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Master\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the customers.
     */
    public function index(Request $request)
    {
        $this->authorize('customer.view');
        $filters = $request->only(['search', 'is_active', 'sort', 'direction']);
        $customers = $this->service->getPaginated($filters);

        return Inertia::render('Customer/CustomerIndex', [
            'filters' => $filters,
            'customers' => $customers,
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('customer.view');
        $data = $customer->toArray();
        $summary = $this->service->getSummaryStats($customer);
        $data['summary'] = $summary;
        $data['recent_transactions'] = $summary['recent_transactions'];

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = $request->user()->business_id;
        $data['created_by'] = $request->user()->id;

        $this->service->create($data);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->service->update($customer, $request->validated());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    /**
     * Delete (soft) the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('customer.delete');
        $this->service->delete($customer);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function importTemplate()
    {
        $this->authorize('customer.create');
        $headers = ['Nama Lengkap', 'Nomor Telepon', 'Email', 'Alamat', 'Tanggal Lahir', 'Jenis Kelamin', 'Catatan', 'Status'];
        $dummyData = ['Budi Santoso', '081234567890', 'budi@example.com', 'Jl. Merdeka No. 45', '1990-05-15', 'Laki-laki', 'Pelanggan VIP', 'Aktif'];

        return response()->stream(function () use ($headers, $dummyData) {
            $file = fopen('php://output', 'w');
            fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            fputcsv($file, $dummyData);
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_pelanggan.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $this->authorize('customer.create');
        $request->validate(['file' => 'required|mimes:csv,txt|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        \App\Jobs\Customer\ImportCustomerJob::dispatch(auth()->user(), $path);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses impor CSV sedang berjalan di latar belakang.'
        );
    }

    /**
     * Search active customers for POS usage.
     */
    public function search(Request $request)
    {
        $this->authorize('customer.view');
        $query = $request->input('q', '');
        $limit = $request->input('limit', 10);
        $results = $this->service->searchActive($query, $limit);

        return response()->json($results);
    }

    public function export(Request $request)
    {
        $this->authorize('report.customer');
        $filters = $request->only(['search', 'is_active']);

        \App\Jobs\Customer\ExportCustomerJob::dispatch(auth()->user(), $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Proses ekspor CSV sedang berjalan di latar belakang.'
        );
    }
}
