<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

class JSONLDEventFormatterTest extends \PHPUnit_Framework_TestCase
{
    private function getJSONEventFromFile($fileName)
    {
        $jsonEvent = file_get_contents(
            __DIR__ . '/../../samples/' . $fileName
        );

        return $jsonEvent;
    }

    /**
     * @test
     */
    public function it_formats_included_terms()
    {
        $includedProperties = [
            'id',
            'terms.eventtype',
            'terms.theme'
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Geschiedenis","domain":"theme","id":"1.11.0.0.0"},{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_excludes_all_terms_when_none_are_included()
    {
        $includedProperties = [
            'id',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58"}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_excludes_other_terms_when_some_are_included()
    {
        $includedProperties = [
            'id',
            'terms.eventtype'
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new JSONLDEventFormatter($includedProperties);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
        );
    }
}
