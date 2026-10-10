<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * What the product page gets from this service. Free of the database.
 */
class RestockPageData
{
    public const COOKIE = 'stockNotify';
    private const NOTICE_KEY = 'restock_notice';

    /**
     * @param mixed $cookieValue The visitor's stockNotify cookie: the product they last asked about
     * @param array{status: string, message: string}|null $notice What happened to this visitor's own request, if anything
     * @return array{restockNotifySet: bool, restockNotice: ?array}
     */
    public static function build(int $productId, mixed $cookieValue, ?array $notice): array
    {
        return [
            'restockNotifySet' => $cookieValue !== null && (string) $cookieValue === (string) $productId,
            'restockNotice' => $notice,
        ];
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
