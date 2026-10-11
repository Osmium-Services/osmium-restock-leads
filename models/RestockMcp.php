<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * Restock Leads' tool for the /mcp endpoint, offered through core's `mcp.tools` hook: one read-only summary of
 * which products customers are waiting for. It returns counts and dates only and never an email address, since
 * that is the one piece of personal data the service holds. The service only collects requests, nothing is
 * emailed when stock returns, so the summary also points out products that are available again but still have
 * customers waiting. There are no write tools.
 */
class RestockMcp
{
    private const LIST_LIMIT = 20;
    private const MAX_NAME_LENGTH = 80;

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>> tool descriptors
     */
    public static function tools(array $payload): array
    {
        return [
            [
                'name' => 'restock_demand',
                'description' => 'Which products customers have asked to hear about when back in stock: open requests, distinct customers and products overall, the top 20 products by customers waiting (with first and last request date and whether the product is out of stock, restocking with its date, or available again), and the products that are available again but still have customers waiting. Counts and dates only, never an email address. Nothing is emailed automatically when stock returns, so those customers need telling by hand. Read only.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
                'handler' => self::class . '::restockDemand',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $call
     * @return array<string, mixed>
     */
    public static function restockDemand(array $call): array
    {
        $leads = new RestockLead($call['dataSource']);

        return self::shape($leads->totals(), $leads->demand());
    }

    /**
     * @param array<string, mixed> $totals requests, customers and products from restock-lead-totals.sql
     * @param array<int, array<string, mixed>> $rows from restock-lead-demand.sql, most customers first
     * @return array<string, mixed>
     */
    public static function shape(array $totals, array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $products[] = self::product($row);
        }

        $availableAgain = \array_values(\array_filter($products, static fn(array $product): bool => $product['state'] === 'available again'));

        return [
            'open_requests' => (int) ($totals['requests'] ?? 0),
            'distinct_customers' => (int) ($totals['customers'] ?? 0),
            'products_with_requests' => (int) ($totals['products'] ?? 0),
            'top_products' => \array_slice($products, 0, self::LIST_LIMIT),
            'available_again_with_customers_waiting' => \array_slice($availableAgain, 0, self::LIST_LIMIT),
            'available_again_product_count' => \count($availableAgain),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function product(array $row): array
    {
        $state = self::state($row);

        return [
            'id' => (int) $row['product_id'],
            'name' => $state === 'deleted product' ? '(deleted product)' : self::name($row),
            'reference' => $state === 'deleted product' || ($row['reference'] ?? '') === '' ? null : self::clean((string) $row['reference']),
            'requests' => (int) $row['requests'],
            'customers' => (int) $row['customers'],
            'first_request' => \substr((string) $row['first_request'], 0, 10),
            'last_request' => \substr((string) $row['last_request'], 0, 10),
            'state' => $state,
            'restock_at' => $state === 'restocking' && !empty($row['restock_at']) ? \substr((string) $row['restock_at'], 0, 10) : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function state(array $row): string
    {
        $gone = $row['model_name'] === null || $row['product_deleted_at'] !== null;
        if ($gone) return 'deleted product';
        if ((int) $row['available'] === 1) return 'available again';
        if ((int) $row['restocking'] === 1) return 'restocking';

        return 'out of stock';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function name(array $row): string
    {
        return self::clean(\trim((string) ($row['brand_name'] ?? '') . ' ' . (string) $row['model_name']));
    }

    private static function clean(string $text): string
    {
        $clean = \trim((string) \preg_replace('/\s+/u', ' ', (string) \preg_replace('/[\p{C}]+/u', ' ', $text)));

        return \mb_strlen($clean) > self::MAX_NAME_LENGTH ? \mb_substr($clean, 0, self::MAX_NAME_LENGTH) . '…' : $clean;
    }
}
