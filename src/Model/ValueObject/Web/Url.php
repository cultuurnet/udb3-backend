<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;
use InvalidArgumentException;

class Url
{
    use IsString;
    use MatchesRegexPattern;

    public function __construct(string $value)
    {
        try {
            $this->guardRegexPattern('/\\Ahttp[s]?:\\/\\//', strtolower($value));
        } catch (InvalidArgumentException $exception) {
            throw new InvalidUrl('Given string is not a valid url.');
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidUrl('Given string is not a valid url.');
        }

        $this->setValue($value);
    }

    public function getDomain(): string
    {
        return parse_url($this->toString(), PHP_URL_HOST);
    }

    public function getFragmentIdentifier(): ?string
    {
        return parse_url($this->toString(), PHP_URL_FRAGMENT);
    }

    public function getPath(): ?string
    {
        return parse_url($this->toString(), PHP_URL_PATH);
    }

    public function getPort(): ?PortNumber
    {
        $portNumber = parse_url($this->toString(), PHP_URL_PORT);
        return $portNumber ? new PortNumber($portNumber) : null;
    }

    public function getQueryString(): ?string
    {
        return parse_url($this->toString(), PHP_URL_QUERY);
    }
}
