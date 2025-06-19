<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JSONLDEventFormatterTest extends TestCase
{
    private CalendarSummaryRepositoryInterface&MockObject $calendarSummaryRepository;

    public function setUp(): void
    {
        $this->calendarSummaryRepository = $this->createMock(CalendarSummaryRepositoryInterface::class);
        $this->calendarSummaryRepository->method('get')->willReturn('Vrijdag');
    }

    private function getJSONEventFromFile(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/../../samples/' . $fileName);
    }

    /**
     * @test
     */
    public function it_formats_included_terms(): void
    {
        $includedProperties = [
            'id',
            'terms.eventtype',
            'terms.theme',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Geschiedenis","domain":"theme","id":"1.11.0.0.0"},{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_excludes_all_terms_when_none_are_included(): void
    {
        $includedProperties = [
            'id',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58"}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_excludes_other_terms_when_some_are_included(): void
    {
        $includedProperties = [
            'id',
            'terms.eventtype',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        /* @codingStandardsIgnoreStart */
        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
        );
        /* @codingStandardsIgnoreEnd */
    }

    /**
     * @test
     */
    public function it_can_export_status(): void
    {
        $includedProperties = [
            'id',
            'status',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_status.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","status":{"type":"Available"}}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_booking_availability(): void
    {
        $includedProperties = [
            'id',
            'bookingAvailability',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_booking_availability.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","bookingAvailability":{"type":"Unavailable"}}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_calendar_summary(): void
    {
        $includedProperties = [
            'id',
            'calendarSummary',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_booking_availability.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","calendarSummary":"Vrijdag"}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_videos(): void
    {
        $includedProperties = [
            'id',
            'videos',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_multiple_videos.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"https:\/\/udb-silex-acc.uitdatabank.be\/event\/0c70b8f3-66a0-4532-959f-2e13b4624f04","videos":[{"id":"6d787098-3082-4a0f-a510-1df4597ae02f","url":"https:\/\/www.youtube.com\/watch?v=cEItmb_a20D","embedUrl":"https:\/\/www.youtube.com\/embed\/cEItmb_a20D","language":"nl","copyrightHolder":"Copyright afgehandeld door YouTube"},{"id":"192a07d9-049b-4c2a-bc94-e46b7a557529","url":"https:\/\/www.youtube.com\/watch?v=sXYtmb_q19C","embedUrl":"https:\/\/www.youtube.com\/embed\/sXYtmb_q19C","language":"fr","copyrightHolder":"publiq"}]}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_exports_attendance(): void
    {
        $includedProperties = [
            'id',
            'attendance',
        ];
        $eventWithAttendanceMode = $this->getJSONEventFromFile('event_with_attendance_mode.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithAttendanceMode);

        $this->assertEquals(
            '{"@id":"https:\/\/udb-silex-acc.uitdatabank.be\/event\/0c70b8f3-66a0-4532-959f-2e13b4624f04","attendanceMode":"mixed","onlineUrl":"https:\/\/www.publiq.be\/livestream"}',
            $event
        );
    }
}
