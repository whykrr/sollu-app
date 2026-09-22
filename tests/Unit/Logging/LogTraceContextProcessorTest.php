<?php

namespace Tests\Unit\Logging;

use App\Logging\Processors\LogTraceContextProcessor;
use App\Models\CockpitUser;
use Illuminate\Support\Facades\Auth;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class LogTraceContextProcessorTest extends TestCase
{
    protected function tearDown(): void
    {
        LogTraceContextProcessor::resetTraceId();
        parent::tearDown();
    }

    public function test_it_attaches_trace_id_and_environment(): void
    {
        $processor = new LogTraceContextProcessor;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'json_daily',
            level: Level::Info,
            message: 'Testing structured logging',
            context: [],
            extra: []
        );

        $processed = $processor($record);

        $this->assertArrayHasKey('trace_id', $processed->extra);
        $this->assertNotEmpty($processed->extra['trace_id']);
        $this->assertArrayHasKey('environment', $processed->extra);
        $this->assertSame(app()->environment(), $processed->extra['environment']);
    }

    public function test_it_attaches_authenticated_cockpit_user_context(): void
    {
        $processor = new LogTraceContextProcessor;

        $cockpitUser = new CockpitUser([
            'name' => 'Admin Cockpit',
            'email' => 'admin@sollu.id',
            'status' => 'active',
        ]);
        $cockpitUser->id = '018f10b2-74d1-4cb3-bb18-a6e5b4c10a1b';

        Auth::guard('cockpit')->setUser($cockpitUser);

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'loki',
            level: Level::Error,
            message: 'Test exception',
            context: [],
            extra: []
        );

        $processed = $processor($record);

        $this->assertSame('018f10b2-74d1-4cb3-bb18-a6e5b4c10a1b', $processed->extra['cockpit_user_id']);
        $this->assertSame('admin@sollu.id', $processed->extra['cockpit_user_email']);
    }

    public function test_custom_trace_id_can_be_set(): void
    {
        LogTraceContextProcessor::setTraceId('custom-trace-uuid-1234');

        $processor = new LogTraceContextProcessor;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'json_daily',
            level: Level::Debug,
            message: 'Trace test',
            context: [],
            extra: []
        );

        $processed = $processor($record);

        $this->assertSame('custom-trace-uuid-1234', $processed->extra['trace_id']);
    }
}
