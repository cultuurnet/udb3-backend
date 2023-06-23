<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class NewlineToBreakTagStringFilter implements StringFilterInterface
{
    private bool $closeTag = true;

    public function filter(string $string): string
    {
        // nl2br() only appends <br /> after each \n but does not remove the \n
        $breakTag = $this->closeTag ? '<br />' : '<br>';
        return str_replace("\n", $breakTag, $string);
    }

    public function closeTag(bool $closeTag = true): void
    {
        $this->closeTag = $closeTag;
    }
}
