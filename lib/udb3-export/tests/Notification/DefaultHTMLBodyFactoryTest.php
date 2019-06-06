<?php

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

class DefaultHTMLBodyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_returns_a_html_body_message_with_download_link()
    {
        $eventExportResult = new EventExportResult('http://google.be');
        $defaultHTMLBodyFactory = new DefaultHTMLBodyFactory();

        $expectedBody = '<p>Beste, <br /><br />Hierbij vind je de link naar de door jou geëxporteerde documenten uit UiTdatabank: <a href="http://google.be">http://google.be</a><br /><br />
        Mocht je vragen hebben, of meer informatie wensen over onze diensten, kan je terecht bij vragen@uitdatabank.be.<br /><br />
        Met vriendelijke groeten,<br />Het UiTdatabank team</p>';

        $this->assertEquals(
            $expectedBody,
            $defaultHTMLBodyFactory->getBodyFor($eventExportResult)
        );

    }
}
