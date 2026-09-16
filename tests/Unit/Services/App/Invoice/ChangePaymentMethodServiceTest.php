<?php

namespace Tests\Unit\Services\App\Invoice;

use App\Models\Invoice;
use App\Services\App\Invoice\ChangePaymentMethodService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ChangePaymentMethodServiceTest extends TestCase
{
    protected ChangePaymentMethodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChangePaymentMethodService;
    }

    public function test_it_changes_payment_method_to_manual_successfully(): void
    {
        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $invoiceMock->invoice_number = 'INV-2026-001';
        $invoiceMock->total_amount = 100000.0;

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

        $paymentsQueryMock = Mockery::mock(HasMany::class);
        $invoiceMock->shouldReceive('payments')->andReturn($paymentsQueryMock);

        $pendingPaymentsMock = Mockery::mock(HasMany::class);
        $paymentsQueryMock->shouldReceive('where')->with('status', 'pending')->andReturn($pendingPaymentsMock);
        $pendingPaymentsMock->shouldReceive('delete')->once()->andReturn(1);

        $paymentsQueryMock->shouldReceive('create')
            ->once()
            ->withArgs(function ($args) {
                return $args['amount'] === 100000.0 &&
                       $args['payment_method'] === 'manual' &&
                       $args['status'] === 'pending' &&
                       str_starts_with($args['payment_reference'], 'INV-2026-001-MANUAL-');
            })
            ->andReturn(new \App\Models\Payment);

        $this->service->execute($invoiceMock, 'manual');
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
