<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * The handlers for the core hooks this service listens to.
 */
class RestockHooks
{
    /**
     * product.page.data: whether this visitor already asked about the product, and the one-time notice.
     *
     * @param array{productId: int, dataSource: object} $payload
     */
    public static function productPageData(array $payload): array
    {
        $hasSession = isset($_SESSION) && \is_array($_SESSION);
        $notice = $hasSession ? RestockPageData::takeNotice($_SESSION) : null;

        return RestockPageData::build(
            productId: (int) $payload['productId'],
            cookieValue: $_COOKIE[RestockPageData::COOKIE] ?? null,
            notice: $notice,
        );
    }
}
