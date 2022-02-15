<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class UrlEncodingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (isset($data->url)) {
            $data->url = $this->encode($data->url);
        }

        return $request->withParsedBody($data);
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
}
