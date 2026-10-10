<?php

declare(strict_types=1);

/**
 * Restock notification requests, rendered by Osmium\Modules\Admin\Core\ListView.
 * Read-only. Customers who asked to be told when a product is back in stock.
 */
return [
    'leads' => [
        'id' => 'restock-leads',
        'title' => 'Restock Notification Requests',
        'icon' => 'bx-bell',
        'noun' => ['lead', 'leads'],
        'order' => null,
        'emptyText' => 'No restock notification requests',
        'columns' => [
            [
                'key' => 'model_name',
                'label' => 'Product',
                'type' => 'html',
                'fill' => true,
                'render' => fn(array $row, array $context): string => $row['model_name']
                    ? \htmlspecialchars((string) $row['model_name'])
                    : '<span class="text-muted">Product #' . (int) $row['product_id'] . '</span>',
            ],
            ['key' => 'reference', 'label' => 'Reference', 'type' => 'text', 'code' => true, 'fallback' => '-', 'nowrap' => true],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'html',
                'nowrap' => true,
                'render' => function (array $row, array $context): string {
                    $email = \htmlspecialchars((string) ($row['email'] ?? ''));

                    return '<a href="mailto:' . $email . '">' . $email . '</a>';
                },
            ],
            [
                'key' => 'created_at',
                'label' => 'Requested',
                'type' => 'text',
                'small' => true,
                'nowrap' => true,
                'sortKey' => 'created_at',
                'format' => fn(string $value): string => \date('j M Y, g:ia', \strtotime($value)),
            ],
        ],
    ],
];
