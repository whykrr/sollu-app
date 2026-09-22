<?php

namespace App\Logging\Processors;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LogTraceContextProcessor implements ProcessorInterface
{
    /**
     * Unique request trace ID for log correlation.
     */
    protected static ?string $traceId = null;

    /**
     * Get or generate the current request trace ID.
     */
    public static function getTraceId(): string
    {
        if (static::$traceId === null) {
            static::$traceId = (string) Str::uuid();
        }

        return static::$traceId;
    }

    /**
     * Reset trace ID (useful for long-running queue workers or tests).
     */
    public static function resetTraceId(): void
    {
        static::$traceId = null;
    }

    /**
     * Set a custom trace ID (e.g. from upstream HTTP header X-Trace-Id).
     */
    public static function setTraceId(string $traceId): void
    {
        static::$traceId = $traceId;
    }

    /**
     * Process the log record and attach contextual metadata.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;

        $extra['trace_id'] = static::getTraceId();
        $extra['environment'] = app()->environment();

        if (app()->runningInConsole()) {
            $extra['runtime'] = 'cli';
            $extra['argv'] = $_SERVER['argv'] ?? [];
        } else {
            $extra['runtime'] = 'http';
            $extra['url'] = Request::fullUrl();
            $extra['method'] = Request::method();
            $extra['ip'] = Request::ip();
            $extra['route'] = Request::route()?->getName() ?: Request::path();
            $extra['user_agent'] = Request::userAgent();
        }

        // Authenticated user & tenant context
        $user = Auth::user();
        if ($user) {
            $extra['user_id'] = $user->id ?? null;
            $extra['business_id'] = $user->business_id ?? null;
            $extra['outlet_id'] = $user->outlet_id ?? null;
        }

        $cockpitUser = Auth::guard('cockpit')->user();
        if ($cockpitUser) {
            $extra['cockpit_user_id'] = $cockpitUser->id ?? null;
            $extra['cockpit_user_email'] = $cockpitUser->email ?? null;
        }

        return $record->with(extra: $extra);
    }
}
