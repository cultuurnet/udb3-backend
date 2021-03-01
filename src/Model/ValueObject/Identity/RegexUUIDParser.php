<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class RegexUUIDParser implements UUIDParser
{
    /**
     * @var string
     */
    private $regex;

    /**
     * @var string
     */
    private $idName;

    /**
     * @var int
     */
    private $matchIndex;

    /**
     * @param string $regex
     * @param string $idName
     * @param int $matchIndex
     */
    public function __construct($regex, $idName = 'ID', $matchIndex = 1)
    {
        $this->regex = $regex;
        $this->idName = $idName;
        $this->matchIndex = $matchIndex;
    }

    /**
     * @inheritdoc
     */
    public function fromUrl(Url $url)
    {
        $url = $url->toString();

        $matches = [];
        preg_match($this->regex, $url, $matches);

        if (count($matches) > 1) {
            return new UUID($matches[$this->matchIndex]);
        } else {
            throw new \InvalidArgumentException("No {$this->idName} found in given Url.");
        }
    }
}
