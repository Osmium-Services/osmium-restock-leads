<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * A notify-me request as the form sends it, checked. Free of the database.
 */
class RestockRequest
{
    /** Shown for every bot-check refusal, so a bot never learns which check failed */
    public const TECHNICAL_PROBLEM = 'There was a technical problem sending your request. Please try again later';

    /**
     * @param array{email?: mixed, pid?: mixed} $input
     * @return array{email: string, productId: int}|null Null when the email or product id is not usable
     */
    public static function parse(array $input): ?array
    {
        $email = \trim((string) ($input['email'] ?? ''));
        $productId = \filter_var($input['pid'] ?? null, FILTER_VALIDATE_INT);

        $usable = \filter_var($email, FILTER_VALIDATE_EMAIL) !== false && $productId !== false && $productId > 0;
        if (!$usable) return null;

        return ['email' => $email, 'productId' => $productId];
    }
}
