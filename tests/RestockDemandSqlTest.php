<?php

declare(strict_types=1);

namespace Tests\Services\RestockLeads;

use Osmium\Services\RestockLeads\Models\RestockLead;
use Tests\Integration\DatabaseTestCase;

require_once __DIR__ . '/../models/RestockLead.php';

/**
 * The SQL behind restock_demand, run against real rows: closed requests do not count, the same email in a
 * different letter case is one customer, and each product carries its current stock state.
 */
class RestockDemandSqlTest extends DatabaseTestCase
{
    private RestockLead $leads;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leads = new RestockLead($this->db);

        $prefix = $this->db->tablePrefix();
        $tableExists = $this->db->prepare("SHOW TABLES LIKE '{$prefix}productrestockleads'")->single() !== false;
        if (!$tableExists) $this->markTestSkipped('The Restock Leads table is not installed');
    }

    /** @return array<string, mixed>|null the demand row for one product */
    private function demandFor(int $productId): ?array
    {
        foreach ($this->leads->demand() as $row) {
            if ((int) $row['product_id'] === $productId) return $row;
        }

        return null;
    }

    private function closeLead(int $productId, string $email): void
    {
        $prefix = $this->db->tablePrefix();
        $this->db->prepare(
            "UPDATE {$prefix}productrestockleads SET deleted_at = NOW() WHERE product_id = :product_id AND email = :email",
            [':product_id' => $productId, ':email' => $email],
        )->execute();
    }

    public function test_requests_count_every_open_row_but_customers_count_each_email_once_whatever_its_case_or_spaces(): void
    {
        $product = $this->createTestProduct();
        $this->leads->create($product, 'sam@example.com');
        $this->leads->create($product, 'SAM@Example.com');
        $this->leads->create($product, ' sam@example.com ');
        $this->leads->create($product, 'pat@example.com');

        $row = $this->demandFor($product);

        $this->assertSame(4, (int) $row['requests']);
        $this->assertSame(2, (int) $row['customers']);
    }

    public function test_closed_requests_are_not_counted(): void
    {
        $product = $this->createTestProduct();
        $this->leads->create($product, 'sam@example.com');
        $this->leads->create($product, 'pat@example.com');
        $this->closeLead($product, 'pat@example.com');

        $row = $this->demandFor($product);

        $this->assertSame(1, (int) $row['requests']);
        $this->assertSame(1, (int) $row['customers']);
    }

    public function test_a_product_whose_every_request_is_closed_is_not_listed(): void
    {
        $product = $this->createTestProduct();
        $this->leads->create($product, 'sam@example.com');
        $this->closeLead($product, 'sam@example.com');

        $this->assertNull($this->demandFor($product));
    }

    public function test_the_row_carries_the_products_name_and_whether_it_is_available_again(): void
    {
        $available = $this->createTestProduct(['status_id' => $this->statusId(true), 'model_name' => 'Panel Available']);
        $unavailable = $this->createTestProduct(['status_id' => $this->statusId(false), 'model_name' => 'Panel Gone']);
        $this->leads->create($available, 'a@example.com');
        $this->leads->create($unavailable, 'b@example.com');

        $this->assertSame('Panel Available', $this->demandFor($available)['model_name']);
        $this->assertSame(1, (int) $this->demandFor($available)['available']);
        $this->assertSame(0, (int) $this->demandFor($unavailable)['available']);
    }

    public function test_a_restocking_status_is_reported_as_restocking(): void
    {
        $prefix = $this->db->tablePrefix();
        $status = $this->db->prepare("SELECT id FROM {$prefix}productstatus WHERE restocking = 1 AND deleted_at IS NULL ORDER BY id LIMIT 1")->single();
        if ($status === false) $this->markTestSkipped('No restocking product status exists');

        $product = $this->createTestProduct(['status_id' => (int) $status['id']]);
        $this->leads->create($product, 'a@example.com');

        $this->assertSame(1, (int) $this->demandFor($product)['restocking']);
    }

    public function test_a_deleted_product_and_a_missing_product_are_still_reported_so_they_can_be_labelled(): void
    {
        $prefix = $this->db->tablePrefix();
        $deleted = $this->createTestProduct();
        $this->db->prepare("UPDATE {$prefix}products SET deleted_at = NOW() WHERE id = :id", [':id' => $deleted])->execute();
        $missing = 987654321;
        $this->leads->create($deleted, 'a@example.com');
        $this->leads->create($missing, 'b@example.com');

        $this->assertNotNull($this->demandFor($deleted)['product_deleted_at']);
        $this->assertNull($this->demandFor($missing)['model_name']);
    }

    public function test_the_overall_totals_move_by_exactly_the_open_requests_customers_and_products_added(): void
    {
        $before = $this->leads->totals();
        $one = $this->createTestProduct();
        $two = $this->createTestProduct();
        $this->leads->create($one, 'zz-sam@example.com');
        $this->leads->create($one, 'ZZ-SAM@example.com');
        $this->leads->create($two, ' zz-sam@example.com ');
        $this->leads->create($two, 'zz-closed@example.com');
        $this->closeLead($two, 'zz-closed@example.com');

        $after = $this->leads->totals();

        $this->assertSame(3, (int) $after['requests'] - (int) $before['requests']);
        $this->assertSame(2, (int) $after['products'] - (int) $before['products']);
        $this->assertSame(1, (int) $after['customers'] - (int) $before['customers']);
    }

    public function test_the_most_wanted_product_comes_first(): void
    {
        $quiet = $this->createTestProduct();
        $popular = $this->createTestProduct();
        $this->leads->create($quiet, 'yy-one@example.com');
        foreach (['yy-a', 'yy-b', 'yy-c', 'yy-d', 'yy-e', 'yy-f'] as $name) {
            $this->leads->create($popular, "{$name}@example.com");
        }

        $order = \array_map(static fn(array $row): int => (int) $row['product_id'], $this->leads->demand());

        $this->assertLessThan(\array_search($quiet, $order, true), \array_search($popular, $order, true));
    }
}
