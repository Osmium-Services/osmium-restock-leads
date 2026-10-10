<?php

declare(strict_types=1);

namespace Tests\Services\RestockLeads;

use Osmium\Services\RestockLeads\Models\RestockRequest;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RestockRequest.php';

/**
 * A notify-me request as the form sends it.
 */
final class RestockRequestTest extends TestCase
{
    public function test_a_valid_email_and_product_id_are_accepted(): void
    {
        $this->assertSame(['email' => 'sam@example.com', 'productId' => 12], RestockRequest::parse(['email' => ' sam@example.com ', 'pid' => '12']));
    }

    public function test_a_bad_email_is_refused(): void
    {
        $this->assertNull(RestockRequest::parse(['email' => 'not-an-email', 'pid' => '12']));
        $this->assertNull(RestockRequest::parse(['pid' => '12']));
    }

    public function test_a_missing_zero_negative_or_non_numeric_product_id_is_refused(): void
    {
        foreach ([null, '0', '-3', 'abc', '1.5'] as $pid) {
            $this->assertNull(RestockRequest::parse(['email' => 'sam@example.com', 'pid' => $pid]), (string) $pid);
        }
    }
}
