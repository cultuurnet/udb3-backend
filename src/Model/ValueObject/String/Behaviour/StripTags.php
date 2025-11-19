<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait StripTags
{
    private function stripTags(string $value): string
    {
        return strip_tags($value, '<a><em><li><ol><p><strong><ul>');
    }
}
