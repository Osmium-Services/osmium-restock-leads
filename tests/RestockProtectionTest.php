<?php

declare(strict_types=1);

namespace Tests\Services\RestockLeads;

use Osmium\Services\RestockLeads\Models\RestockProtection;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RestockProtection.php';

/**
 * Bot protection is optional and provider-agnostic: only an explicit failure stops a request.
 */
final class RestockProtectionTest extends TestCase
{
    public function test_no_protection_installed_lets_the_request_through(): void
    {
        $this->assertTrue(RestockProtection::passed([]));
    }

    public function test_a_passed_check_lets_the_request_through(): void
    {
        $this->assertTrue(RestockProtection::passed([true]));
    }

    public function test_a_failed_check_stops_the_request(): void
    {
        $this->assertFalse(RestockProtection::passed([false]));
    }

    public function test_one_failure_among_passes_stops_the_request(): void
    {
        $this->assertFalse(RestockProtection::passed([true, false]));
    }
}
