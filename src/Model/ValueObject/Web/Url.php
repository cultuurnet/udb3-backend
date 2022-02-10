<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class Url
{
    use IsString;
    use MatchesRegexPattern;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->guardString($value);
        $this->guardRegexPattern('/\\Ahttp[s]?:\\/\\//', strtolower($value));

        $value = $this->encode($value);

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Given string is not a valid url.');
        }

        $this->setValue($value);
    }

    private function encode(string $value): string
    {
        // Take into account already encoded urls by first decoding them.
        $value = urldecode($value);

        // Encode the url but revert various semantic characters.
        $value = urlencode($value);

        // Revert meaningful characters.
        // Taken from https://developers.google.com/maps/url-encoding#special-characters
        // Based on https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
        $entities = ['%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D'];
        $replacements = ['!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']'];

        return str_replace(
            $entities,
            $replacements,
            $value
        );
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
