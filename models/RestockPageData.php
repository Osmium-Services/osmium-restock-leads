<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * What the product page gets from this service. Free of the database.
 */
class RestockPageData
{
    private const NOTICE_KEY = 'restock_notice';
    private const REQUESTED_KEY = 'restock_requested';

    /**
     * @param bool $requested Whether this visitor already asked about the product (see hasRequested())
     * @param array{status: string, message: string}|null $notice What happened to this visitor's own request, if anything
     * @return array{restockNotifySet: bool, restockNotice: ?array}
     */
    public static function build(bool $requested, ?array $notice): array
    {
        return [
            'restockNotifySet' => $requested,
            'restockNotice' => $notice,
        ];
    }

    /** Remember in the session that this visitor asked to hear about the product (no cookie of our own) */
    public static function rememberRequest(array &$session, int $productId): void
    {
        $session[self::REQUESTED_KEY][$productId] = true;
    }

    public static function hasRequested(array $session, int $productId): bool
    {
        return isset($session[self::REQUESTED_KEY][$productId]);
    }

    /** The visitor's own notice, read once: it is removed from the session */
    public static function takeNotice(array &$session): ?array
    {
        $notice = $session[self::NOTICE_KEY] ?? null;
        unset($session[self::NOTICE_KEY]);

        return \is_array($notice) ? $notice : null;
    }

    /** Remember what happened to the visitor's request, for the page they are sent back to */
    public static function rememberNotice(array &$session, string $status, string $message): void
    {
        $session[self::NOTICE_KEY] = ['status' => $status, 'message' => $message];
    }
}
