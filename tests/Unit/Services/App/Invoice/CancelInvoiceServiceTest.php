<?php

namespace Tests\Unit\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\User;
use App\Services\App\Invoice\CancelInvoiceService;
use App\Services\App\Outlet\ManageOutletStatusService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CancelInvoiceServiceTest extends TestCase
{
    protected ManageOutletStatusService $manageOutletStatusServiceMock;

    protected CancelInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manageOutletStatusServiceMock = Mockery::mock(ManageOutletStatusService::class);
        $this->service = new CancelInvoiceService($this->manageOutletStatusServiceMock);
    }

    public function test_it_cancels_regular_invoice_successfully(): void
    {
        $userMock = Mockery::mock(User::class)->makePartial();
        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $invoiceMock->business = $businessMock;

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

        $invoiceMock->shouldReceive('update')
            ->once()
            ->with(['status' => Status::Void])
            ->andReturnTrue();

        $itemsQueryMock = Mockery::mock(HasMany::class);
        $invoiceMock->shouldReceive('items')->andReturn($itemsQueryMock);

        $outletAdditionQuery = Mockery::mock(HasMany::class);
        $outletAdditionQuery->shouldReceive('first')->andReturn(null);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'outlet_addition')->andReturn($outletAdditionQuery);

        $recurringPlanQuery = Mockery::mock(HasMany::class);
        $recurringPlanQuery->shouldReceive('first')->andReturn(null);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'recurring_plan')->andReturn($recurringPlanQuery);

        $planRenewalQuery = Mockery::mock(HasMany::class);
        $planRenewalQuery->shouldReceive('exists')->andReturn(false);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'plan_renewal')->andReturn($planRenewalQuery);

        $result = $this->service->execute($invoiceMock, $userMock);

        $this->assertFalse($result['is_outlet_addition']);
        $this->assertSame($invoiceMock, $result['invoice']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
