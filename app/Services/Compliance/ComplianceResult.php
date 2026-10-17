<?php

namespace App\Services\Compliance;

use App\Models\CustomerEntitlement;

final class ComplianceResult
{
    public const HINT_LOGIN = 'login';

    public const HINT_VERIFY_AGE = 'verify_age';

    public const HINT_UPLOAD_ENTITLEMENT = 'upload_entitlement';

    public const HINT_CONTACT = 'contact';

    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly bool $requiresReview = false,
        public readonly array $reasons = [],
        public readonly ?CustomerEntitlement $entitlement = null,
        public readonly ?string $hint = null,
    ) {}

    public static function ok(bool $requiresReview = false, ?CustomerEntitlement $entitlement = null, array $reasons = []): self
    {
        return new self(true, $requiresReview, $reasons, $entitlement);
    }

    public static function deny(string $reason, ?string $hint = null): self
    {
        return new self(false, false, [$reason], null, $hint);
    }
}
