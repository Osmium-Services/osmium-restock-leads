<?php

declare(strict_types=1);

namespace Tests\Services\RestockLeads;

use Osmium\Services\RestockLeads\Models\RestockMcp;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RestockMcp.php';

/**
 * The restock summary the /mcp endpoint offers: counts, dates and product states only, never an email address.
 */
final class RestockMcpTest extends TestCase
{
    private function row(array $overrides = []): array
    {
        return $overrides + [
            'product_id' => '7', 'model_name' => 'Panel 400W', 'reference' => 'P-400', 'restock_at' => null, 'product_deleted_at' => null,
            'brand_name' => 'Seed Globex', 'available' => '0', 'restocking' => '0',
            'requests' => '5', 'customers' => '4', 'first_request' => '2026-10-01 09:00:00', 'last_request' => '2026-10-10 18:30:00',
        ];
    }

    public function test_it_offers_one_read_only_tool(): void
    {
        $tools = RestockMcp::tools([]);

        $this->assertSame(['restock_demand'], \array_column($tools, 'name'));
        $this->assertArrayNotHasKey('write', $tools[0]);
        $this->assertStringContainsString('never an email address', $tools[0]['description']);
    }

    public function test_the_totals_and_a_product_are_shaped(): void
    {
        $shape = RestockMcp::shape(['requests' => '9', 'customers' => '7', 'products' => '2'], [$this->row()]);

        $this->assertSame(9, $shape['open_requests']);
        $this->assertSame(7, $shape['distinct_customers']);
        $this->assertSame(2, $shape['products_with_requests']);
        $this->assertSame([
            'id' => 7, 'name' => 'Seed Globex Panel 400W', 'reference' => 'P-400', 'requests' => 5, 'customers' => 4,
            'first_request' => '2026-10-01', 'last_request' => '2026-10-10', 'state' => 'out of stock', 'restock_at' => null,
        ], $shape['top_products'][0]);
    }

    public function test_the_state_follows_the_product_status_and_a_restock_date_only_shows_while_restocking(): void
    {
        $rows = [
            $this->row(['product_id' => '1', 'available' => '1', 'restock_at' => '2026-12-01']),
            $this->row(['product_id' => '2', 'restocking' => '1', 'restock_at' => '2026-12-01']),
            $this->row(['product_id' => '3', 'restocking' => '1', 'restock_at' => null]),
            $this->row(['product_id' => '4']),
            $this->row(['product_id' => '5', 'model_name' => null, 'brand_name' => null, 'reference' => null, 'available' => null, 'restocking' => null]),
            $this->row(['product_id' => '6', 'product_deleted_at' => '2026-09-01 00:00:00']),
        ];

        $byId = [];
        foreach (RestockMcp::shape([], $rows)['top_products'] as $product) {
            $byId[$product['id']] = $product;
        }

        $this->assertSame('available again', $byId[1]['state']);
        $this->assertNull($byId[1]['restock_at']);
        $this->assertSame('restocking', $byId[2]['state']);
        $this->assertSame('2026-12-01', $byId[2]['restock_at']);
        $this->assertNull($byId[3]['restock_at']);
        $this->assertSame('out of stock', $byId[4]['state']);
        $this->assertSame('deleted product', $byId[5]['state']);
        $this->assertSame('(deleted product)', $byId[5]['name']);
        $this->assertNull($byId[5]['reference']);
        $this->assertSame('deleted product', $byId[6]['state']);
        $this->assertSame('(deleted product)', $byId[6]['name']);
    }

    public function test_products_available_again_with_customers_waiting_are_listed_and_counted(): void
    {
        $rows = [
            $this->row(['product_id' => '1', 'customers' => '9', 'available' => '1']),
            $this->row(['product_id' => '2', 'customers' => '5']),
            $this->row(['product_id' => '3', 'customers' => '2', 'available' => '1']),
        ];

        $shape = RestockMcp::shape([], $rows);

        $this->assertSame([1, 3], \array_column($shape['available_again_with_customers_waiting'], 'id'));
        $this->assertSame(2, $shape['available_again_product_count']);
    }

    public function test_the_lists_are_capped_but_the_available_again_count_covers_every_product(): void
    {
        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $rows[] = $this->row(['product_id' => (string) $i, 'available' => '1']);
        }

        $shape = RestockMcp::shape([], $rows);

        $this->assertCount(20, $shape['top_products']);
        $this->assertCount(20, $shape['available_again_with_customers_waiting']);
        $this->assertSame(30, $shape['available_again_product_count']);
    }

    public function test_nothing_waiting_gives_zeros_and_empty_lists(): void
    {
        $shape = RestockMcp::shape([], []);

        $this->assertSame(0, $shape['open_requests']);
        $this->assertSame([], $shape['top_products']);
        $this->assertSame(0, $shape['available_again_product_count']);
    }

    public function test_an_email_in_a_row_can_never_reach_the_output(): void
    {
        $row = $this->row(['email' => 'jo.bloggs@example.com', 'emails' => 'a@b.co,c@d.co']);

        $json = (string) \json_encode(RestockMcp::shape(['requests' => '1', 'customers' => '1', 'products' => '1', 'email' => 'x@y.co'], [$row]));

        $this->assertStringNotContainsString('@', $json);
        $this->assertStringNotContainsString('email', $json);
    }

    public function test_a_product_name_is_cleaned_and_cut(): void
    {
        $shape = RestockMcp::shape([], [$this->row(['brand_name' => "Evil\x00\n", 'model_name' => \str_repeat('x', 200)])]);

        $name = $shape['top_products'][0]['name'];
        $this->assertStringStartsWith('Evil ', $name);
        $this->assertSame(81, \mb_strlen($name)); // 80 characters and the ellipsis
    }
}
