<?php

namespace Tests\Unit\Jobs;

use App\Club\Callups\CallupNoticeMode;
use App\Jobs\Club\DeliverAnnouncementEmail;
use App\Jobs\Club\SendCallupNotice;
use Tests\TestCase;

/**
 * Los jobs de mail con RateLimited('mail') no pueden usar `tries` fijo: cada vez que el límite los
 * devuelve a la cola cuenta como intento, y en un envío grande se daban por fallidos sin mandar nada.
 */
class RateLimitedMailJobsTest extends TestCase
{
    public function test_rate_limited_mail_jobs_retry_by_time_and_stop_on_real_errors(): void
    {
        $jobs = [
            new DeliverAnnouncementEmail(1, 1),
            new SendCallupNotice(1, 1, [1], CallupNoticeMode::cases()[0], now()->toIso8601String()),
        ];

        foreach ($jobs as $job) {
            $this->assertFalse(property_exists($job, 'tries') && $job->tries !== null, $job::class.' no debe fijar $tries.');
            $this->assertSame(3, $job->maxExceptions, $job::class);
            $this->assertTrue($job->retryUntil()->getTimestamp() >= now()->addMinutes(30)->getTimestamp(), $job::class);
        }
    }
}
