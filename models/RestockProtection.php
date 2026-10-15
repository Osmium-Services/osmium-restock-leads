<?php

declare(strict_types=1);

namespace Osmium\Services\RestockLeads\Models;

/**
 * Reads the answers of whatever bot protection is installed. The form never names a provider: each one
 * answers the form.verify hook with true (passed), false (failed) or nothing (not involved).
 */
class RestockProtection
{
    /** @param array<int, mixed> $answers What the form.verify handlers returned */
    public static function passed(array $answers): bool
    {
        return !\in_array(false, $answers, true);
    }
}
