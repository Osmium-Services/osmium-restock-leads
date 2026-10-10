<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

use Osmium\Core\Library\OsmiumPDO;
use Osmium\Core\Models\Model;

/**
 * Customers who asked to be told when a product is back in stock.
 */
class RestockLead extends Model
{
    protected ?string $sqlDir = __DIR__ . '/sql';

    public function __construct(OsmiumPDO $database)
    {
        parent::__construct(database: $database, tableName: 'productrestockleads');
    }

    public function create(int $productId, string $email): void
    {
        $this->database->prepare(
            sql: $this->loadSqlFile('restock-lead-insert.sql'),
            bindings: [':product_id' => $productId, ':email' => $email],
        )->execute();
    }

    /** @return array<int, array<string, mixed>> Every live lead with its product, newest first */
    public function listAll(): array
    {
        return $this->database->prepare(sql: $this->loadSqlFile('restock-lead-list.sql'), bindings: [])->resultset();
    }
}
