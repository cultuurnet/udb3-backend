<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class RegexUuidParser implements UuidParser
{
    private string $regex;

    private string $idName;

    private int $matchIndex;

    public function __construct(string $regex, string $idName = 'ID', int $matchIndex = 1)
    {
        $this->regex = $regex;
        $this->idName = $idName;
        $this->matchIndex = $matchIndex;
    }

    public function fromUrl(Url $url): Uuid
    {
        $url = $url->toString();

        $matches = [];
        preg_match($this->regex, $url, $matches);

        if (count($matches) > 1) {
            return new Uuid($matches[$this->matchIndex]);
        }

        throw new \InvalidArgumentException("No {$this->idName} found in given Url.");
    }
}
