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
        $session = [];
        RestockPageData::rememberRequest($session, 12);

        $this->assertTrue(RestockPageData::hasRequested($session, 12));
        $this->assertTrue(RestockPageData::build(RestockPageData::hasRequested($session, 12), null)['restockNotifySet']);
    }

    public function test_asking_about_one_product_does_not_mark_another(): void
    {
        $session = [];
        RestockPageData::rememberRequest($session, 12);

        $this->assertFalse(RestockPageData::hasRequested($session, 13));
        $this->assertFalse(RestockPageData::hasRequested([], 12));
    }

    public function test_several_products_can_be_asked_about_in_one_session(): void
    {
        $session = [];
        RestockPageData::rememberRequest($session, 12);
        RestockPageData::rememberRequest($session, 13);

        $this->assertTrue(RestockPageData::hasRequested($session, 12));
        $this->assertTrue(RestockPageData::hasRequested($session, 13));
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

        $this->assertSame($notice, RestockPageData::build(false, $notice)['restockNotice']);
    }
}
