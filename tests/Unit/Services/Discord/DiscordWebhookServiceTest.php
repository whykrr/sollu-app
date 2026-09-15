<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordWebhookService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordWebhookServiceTest extends TestCase
{
    protected DiscordWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DiscordWebhookService;
    }

    public function test_it_skips_sending_in_non_production_when_allow_non_prod_is_false(): void
    {
        Config::set('app.env', 'local');
        Config::set('services.discord.allow_non_prod', false);
        Config::set('services.discord.channels.merchant_registration', 'https://discord.com/api/webhooks/123/abc');

        Http::fake();

        $result = $this->service->send('merchant_registration', ['title' => 'Test']);

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    public function test_it_returns_false_when_webhook_url_is_not_configured(): void
    {
        Config::set('app.env', 'production');
        Config::set('services.discord.channels.merchant_registration', null);
        Config::set('services.discord.channels.default', null);

        Http::fake();

        $result = $this->service->send('merchant_registration', ['title' => 'Test']);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_it_sends_json_payload_to_clean_webhook_url(): void
    {
        Config::set('app.env', 'production');
        Config::set('services.discord.channels.merchant_registration', 'https://discord.com/api/webhooks/123/abc/slack');

        Http::fake([
            'https://discord.com/api/webhooks/123/abc' => Http::response([], 200),
        ]);

        $embed = ['title' => 'Merchant Baru'];
        $components = [
            [
                'type' => 1,
                'components' => [
                    ['type' => 2, 'style' => 5, 'label' => 'Detail', 'url' => 'https://app.sollu.id'],
                ],
            ],
        ];

        $result = $this->service->send('merchant_registration', $embed, $components);

        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($embed, $components) {
            return $request->url() === 'https://discord.com/api/webhooks/123/abc'
                && $request['embeds'] === [$embed]
                && $request['components'] === $components;
        });
    }

    public function test_it_sends_multipart_with_attachment(): void
    {
        Config::set('app.env', 'production');
        Config::set('services.discord.channels.payment_validation', 'https://discord.com/api/webhooks/456/def');

        Http::fake([
            'https://discord.com/api/webhooks/456/def' => Http::response([], 200),
        ]);

        $embed = ['title' => 'Validasi Pembayaran'];
        $attachment = [
            'contents' => 'fake-image-binary-data',
            'filename' => 'struk.jpg',
        ];

        $result = $this->service->send('payment_validation', $embed, [], $attachment);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/webhooks/456/def'
                && $request->isMultipart();
        });
    }

    public function test_it_handles_http_failure_gracefully(): void
    {
        Config::set('app.env', 'production');
        Config::set('services.discord.channels.default', 'https://discord.com/api/webhooks/789/ghi');

        Http::fake([
            'https://discord.com/api/webhooks/789/ghi' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $result = $this->service->send('default', ['title' => 'Test']);

        $this->assertFalse($result);
    }
}
