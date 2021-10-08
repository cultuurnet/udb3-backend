<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use Psr\Http\Message\ServerRequestInterface;

final class CalendarSummaryParameters
{
    private QueryParameters $queryParameters;

    public function __construct(ServerRequestInterface $request)
    {
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
        $style = $this->queryParameters->get('style', 'text');
        switch ($style) {
            case 'html':
                return 'text/html';
            case 'text':
            default:
                return 'text/plain';
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

    public function getTimeZone(): string
    {
        return $this->queryParameters->get('timeZone', 'Europe/Brussels');
    }
}
