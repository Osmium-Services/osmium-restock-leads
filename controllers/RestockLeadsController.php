<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\RestockLeads\Models\RestockLead;

/**
 * Restock leads admin: a read-only list of customers who asked to hear when a product is back.
 *
 * Routes:
 *   - index() → /admin/restock-leads/
 */
class RestockLeadsController extends AdminController
{
    public function index(): void
    {
        $this->data['admin']['lists'] = require __DIR__ . '/../lists/restock-leads.php';
        $this->data['admin']['restockLeads'] = (new RestockLead($this->osmium->dataSource))->listAll();
        $this->data['admin']['isAdmin'] = $this->isAdmin();

        $this->setView('restock-leads/index.phtml');
    }
}
