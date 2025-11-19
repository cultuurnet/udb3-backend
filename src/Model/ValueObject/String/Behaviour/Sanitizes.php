<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait Sanitizes
{
    private function sanitize(string $value, string $allowed = '<a><em><li><ol><p><strong><ul>'): string
    {
        return strip_tags($value, $allowed);
    }
}
