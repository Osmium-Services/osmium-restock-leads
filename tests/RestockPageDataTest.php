<?php

declare(strict_types=1);

namespace Tests\Services\RestockLeads;

use Osmium\Services\RestockLeads\Models\RestockPageData;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RestockPageData.php';

/**
 * What the product page gets from the restock service.
 */
final class RestockPageDataTest extends TestCase
{
    public function test_the_page_knows_the_visitor_already_asked_about_this_product(): void
    {
        $this->assertTrue(RestockPageData::build(12, '12', null)['restockNotifySet']);
        $this->assertTrue(RestockPageData::build(12, 12, null)['restockNotifySet']);
    }

    public function test_a_cookie_for_another_product_or_none_means_not_set(): void
    {
        $this->assertFalse(RestockPageData::build(12, '13', null)['restockNotifySet']);
        $this->assertFalse(RestockPageData::build(12, null, null)['restockNotifySet']);
    }

    public function test_a_notice_is_remembered_then_read_once(): void
    {
        $session = [];
        RestockPageData::rememberNotice($session, 'sent', 'Done');

        $this->assertSame(['status' => 'sent', 'message' => 'Done'], RestockPageData::takeNotice($session));
        $this->assertNull(RestockPageData::takeNotice($session));
    }

    public function test_the_notice_is_passed_to_the_page(): void
    {
        $notice = ['status' => 'failed', 'message' => 'No'];

        $this->assertSame($notice, RestockPageData::build(1, null, $notice)['restockNotice']);
    }
}
