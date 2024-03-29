<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use DateTime;
use DateTimeInterface;
use stdClass;

final class LegacyTimeSpansParser
{
    public function parse(stdClass $data): StdClass
    {
        $data = clone $data;

        // Rename timeSpans to subEvent
        if (isset($data->timeSpans) && is_array($data->timeSpans) && !isset($data->subEvent)) {
            if (count($data->timeSpans) > 0) {
                $data->subEvent = array_map(
                    function ($timeSpan) {
                        // Rename start to startDate
                        if ($timeSpan instanceof stdClass && isset($timeSpan->start)) {
                            $timeSpan->startDate = $this->formatDateTime($timeSpan->start);
                            unset($timeSpan->start);
                        }
                        // Rename end to endDate
                        if ($timeSpan instanceof stdClass && isset($timeSpan->end)) {
                            $timeSpan->endDate = $this->formatDateTime($timeSpan->end);
                            unset($timeSpan->end);
                        }
                        return $timeSpan;
                    },
                    $data->timeSpans
                );
            }

            unset($data->timeSpans);
        }

        return $data;
    }

    private function formatDateTime(string $dateTime): string
    {
        return (new DateTime($dateTime))->format(DateTimeInterface::ATOM);
    }
}
