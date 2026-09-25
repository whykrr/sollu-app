<?php

namespace Tests\Unit\Services;

use App\Support\Breadcrumbs\BreadcrumbManager;
use Illuminate\Http\Request;
use Tests\TestCase;

class BreadcrumbManagerTest extends TestCase
{
    protected BreadcrumbManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new BreadcrumbManager;
    }

    public function test_it_generates_overview_breadcrumb_for_app_home(): void
    {
        $crumbs = $this->manager->generate('overview', 'app');

        $this->assertCount(1, $crumbs);
        $this->assertEquals('Overview', $crumbs[0]['label']);
        $this->assertNotNull($crumbs[0]['url']);
    }

    public function test_it_generates_dashboard_breadcrumb_for_cockpit_home(): void
    {
        $crumbs = $this->manager->generate('cockpit.dashboard', 'cockpit');

        $this->assertCount(1, $crumbs);
        $this->assertEquals('Dashboard', $crumbs[0]['label']);
        $this->assertNotNull($crumbs[0]['url']);
    }

    public function test_it_resolves_master_products_hierarchy(): void
    {
        $crumbs = $this->manager->generate('master.products.index', 'app');

        $this->assertCount(2, $crumbs);
        $this->assertEquals('Master Produk', $crumbs[0]['label']);
        $this->assertNull($crumbs[0]['url']); // Parent section is non-clickable
        $this->assertEquals('Produk Barang', $crumbs[1]['label']);
        $this->assertNotNull($crumbs[1]['url']);

        $crumbsCreate = $this->manager->generate('master.products.create', 'app');
        $this->assertCount(3, $crumbsCreate);
        $this->assertEquals('Tambah Barang', $crumbsCreate[2]['label']);
        $this->assertNull($crumbsCreate[2]['url']);
    }

    public function test_it_resolves_master_services_hierarchy(): void
    {
        $crumbs = $this->manager->generate('master.services.index', 'app');

        $this->assertCount(2, $crumbs);
        $this->assertEquals('Master Produk', $crumbs[0]['label']);
        $this->assertNull($crumbs[0]['url']); // Parent section is non-clickable
        $this->assertEquals('Produk Layanan', $crumbs[1]['label']);
        $this->assertNotNull($crumbs[1]['url']);

        $crumbsCreate = $this->manager->generate('master.services.create', 'app');
        $this->assertCount(3, $crumbsCreate);
        $this->assertEquals('Tambah Layanan', $crumbsCreate[2]['label']);
        $this->assertNull($crumbsCreate[2]['url']);
    }

    public function test_it_resolves_reports_hierarchy(): void
    {
        $crumbs = $this->manager->generate('reports.sales.index', 'app');

        $this->assertCount(2, $crumbs);
        $this->assertEquals('Laporan', $crumbs[0]['label']);
        $this->assertNull($crumbs[0]['url']);
        $this->assertEquals('Laporan Penjualan', $crumbs[1]['label']);
        $this->assertNotNull($crumbs[1]['url']);
    }

    public function test_it_resolves_cockpit_merchants_and_invoices(): void
    {
        $merchantsCrumbs = $this->manager->generate('cockpit.merchants.index', 'cockpit');
        $this->assertCount(1, $merchantsCrumbs);
        $this->assertEquals('Bisnis & Merchant', $merchantsCrumbs[0]['label']);

        $invoicesCrumbs = $this->manager->generate('cockpit.invoices.index', 'cockpit');
        $this->assertCount(2, $invoicesCrumbs);
        $this->assertEquals('Tagihan & Pembayaran', $invoicesCrumbs[0]['label']);
        $this->assertNull($invoicesCrumbs[0]['url']);
        $this->assertEquals('Invoice Langganan', $invoicesCrumbs[1]['label']);
        $this->assertNotNull($invoicesCrumbs[1]['url']);
    }

    public function test_it_auto_generates_breadcrumbs_for_unmapped_routes(): void
    {
        $crumbs = $this->manager->generate('reports.custom-analytic.index', 'app');

        $this->assertNotEmpty($crumbs);
        $this->assertEquals('Laporan', $crumbs[0]['label']);
        $this->assertEquals('Custom Analytic', $crumbs[1]['label']);
    }

    public function test_it_handles_custom_request_override(): void
    {
        $request = new Request;
        $request->attributes->set('breadcrumbs', [
            ['label' => 'Custom Root', 'url' => '/custom'],
            ['label' => 'Detail Item #123', 'url' => null],
        ]);

        $crumbs = $this->manager->generate('overview', 'app', $request);

        $this->assertCount(2, $crumbs);
        $this->assertEquals('Custom Root', $crumbs[0]['label']);
        $this->assertEquals('/custom', $crumbs[0]['url']);
        $this->assertEquals('Detail Item #123', $crumbs[1]['label']);
        $this->assertNull($crumbs[1]['url']);
    }

    public function test_helper_function_works_seamlessly(): void
    {
        $appCrumbs = generateBreadcrumbs('employees.index');
        $this->assertCount(1, $appCrumbs);
        $this->assertEquals('Pegawai', $appCrumbs[0]['label']);

        $cockpitCrumbs = generateBreadcrumbs('cockpit.dashboard', 'cockpit');
        $this->assertCount(1, $cockpitCrumbs);
        $this->assertEquals('Dashboard', $cockpitCrumbs[0]['label']);
    }
}
