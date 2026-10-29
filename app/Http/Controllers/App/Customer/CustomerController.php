<?php

namespace App\Http\Controllers\App\Customer;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Customer\GetCustomerRequest;
use App\Http\Requests\App\Customer\StoreCustomerRequest;
use App\Http\Requests\App\Customer\UpdateCustomerRequest;
use App\Jobs\Customer\ExportCustomerJob;
use App\Jobs\Customer\ImportCustomerJob;
use App\Models\Master\Customer;
use App\Services\App\Customer\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $service) {}

    /**
     * Display a listing of the customers.
     */
    public function index(GetCustomerRequest $request)
    {
        $filters = $request->only(['search', 'is_active', 'sort', 'direction']);
        $perPage = (int) $request->input('perpage', 15);
        $customers = $this->service->getPaginated($filters, $perPage);

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
        $this->authorize(PermissionEnum::CUSTOMER_VIEW->value);

        if ($customer->business_id !== auth()->user()?->business_id) {
            abort(403);
        }

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
        if ($customer->business_id !== $request->user()?->business_id) {
            abort(403);
        }

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
        $this->authorize(PermissionEnum::CUSTOMER_DELETE->value);

        if ($customer->business_id !== auth()->user()?->business_id) {
            abort(403);
        }

        $this->service->delete($customer);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::DELETE_SUCCESS
        );
    }

    public function importTemplate()
    {
        $this->authorize(PermissionEnum::CUSTOMER_CREATE->value);
        $headers = ['Nama Lengkap', 'Nomor Telepon', 'Email', 'Alamat', 'Tanggal Lahir', 'Jenis Kelamin', 'Catatan', 'Status'];
        $dummyData = ['Budi Santoso', '081234567890', 'budi@example.com', 'Jl. Merdeka No. 45', '1990-05-15', 'Laki-laki', 'Pelanggan VIP', 'Aktif'];

        $export = new class($headers, $dummyData) implements FromArray, WithHeadings
        {
            private $headers;

            private $dummyData;

            public function __construct($headers, $dummyData)
            {
                $this->headers = $headers;
                $this->dummyData = $dummyData;
            }

            public function array(): array
            {
                return [$this->dummyData];
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        $filename = 'template_'.strtolower(class_basename($this)).'.xlsx';

        return Excel::download($export, $filename);
    }

    public function import(Request $request)
    {
        $this->authorize(PermissionEnum::CUSTOMER_CREATE->value);
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        $path = $request->file('file')->store('imports', 'local');

        ImportCustomerJob::dispatch(auth()->user(), $path);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::IMPORT_PROCESSING
        );
    }

    /**
     * Search active customers for POS usage.
     */
    public function search(Request $request)
    {
        $this->authorize(PermissionEnum::CUSTOMER_VIEW->value);
        $query = (string) $request->input('q', '');
        $limit = (int) $request->input('limit', 10);
        $results = $this->service->searchActive($query, $limit);

        return response()->json($results);
    }

    public function export(Request $request)
    {
        $this->authorize(PermissionEnum::CUSTOMER_VIEW->value);
        $filters = $request->only(['search', 'is_active']);

        ExportCustomerJob::dispatch(auth()->user(), $filters);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::EXPORT_PROCESSING
        );
    }
}
