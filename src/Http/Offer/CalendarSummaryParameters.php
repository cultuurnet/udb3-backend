<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarSummaryParameters
{
    public const TEXT = 'text/plain';
    public const HTML = 'text/html';

    private ServerRequestInterface $request;
    private QueryParameters $queryParameters;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->queryParameters = new QueryParameters($request);

        // nl, fr, de, en are the documented allowed values. In the past these were documented as nl_BE, fr_BE, de_BE
        // and en_BE which happened to work because the calendar summary code cuts off everything after the first two
        // characters of the language code. (So fr_blabla also worked for example.) Because those values were documented
        // and are probably used in existing integrations we also need to allow them.
        $this->queryParameters->guardEnum('langCode', ['nl', 'nl_BE', 'fr', 'fr_BE', 'de', 'de_BE', 'en', 'en_BE']);
        $this->queryParameters->guardEnum('style', ['html', 'text']);
        $this->queryParameters->guardEnum('format', ['xs', 'sm', 'md', 'lg']);
    }

    public function getContentType(): string
    {
        // Prioritize "style" query parameter because sometimes a browser will send a default Accept header, for example
        // when accessing the URL directly it will send a text/html Accept header, which would override the style
        // parameter entered by the visitor (for example someone testing the endpoint manually).
        if ($this->queryParameters->get('style') !== null) {
            return $this->getContentTypeFromStyleParameter();
        }

        // Just take the first line of the Accept header. Additionally, ignore everything after the ; like the q
        // parameter for more advanced content negotiation which is never applicable here.
        // So a header like "Accept: text/plain; q=0.2, text/html" will just get interpreted as "text/plain".
        $accept = $this->request->getHeaderLine('Accept');
        $acceptParts = explode(';', $accept);
        $accept = $acceptParts[0];
        if ($accept === self::HTML) {
            return self::HTML;
        }
        return self::TEXT;
    }

    private function getContentTypeFromStyleParameter(): string
    {
        $style = $this->queryParameters->get('style', 'text');
        switch ($style) {
            case 'html':
                return self::HTML;
            case 'text':
            default:
                return self::TEXT;
        }
    }

    public function getLanguageCode(): string
    {
        return $this->queryParameters->get('langCode', 'nl');
    }

    public function getFormat(): string
    {
        return $this->queryParameters->get('format', 'lg');
    }

    public function shouldHidePastDates(): bool
    {
        return  $this->queryParameters->getAsBoolean('hidePast', false);
    }

    public function getTimezone(): string
    {
        return $this->queryParameters->get('timezone', 'Europe/Brussels');
    }
}
