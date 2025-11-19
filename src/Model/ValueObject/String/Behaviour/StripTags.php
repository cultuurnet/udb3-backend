<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait StripTags
{
    private function stripTags(string $value, string $allowed = '<a><em><li><ol><p><strong><ul>'): string
    {
        return strip_tags($value, $allowed);
    }
}
