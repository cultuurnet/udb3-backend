<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;
use PHPUnit\Framework\TestCase;

class DefaultPlainTextBodyFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_plain_text_body_message_with_download_link(): void
    {
        $eventExportResult = new EventExportResult('http://google.be');
        $defaultPlainTextBodyFactory = new DefaultPlainTextBodyFactory();

        $expectedBody = 'Beste,

        Hierbij vind je de link naar de door jou geëxporteerde documenten uit UiTdatabank: http://google.be

        Mocht je vragen hebben, of meer informatie wensen over onze diensten, kan je terecht bij vragen@uitdatabank.be.

        Met vriendelijke groeten,
        Het UiTdatabank team';

        $this->assertEquals(
            $expectedBody,
            $defaultPlainTextBodyFactory->getBodyFor($eventExportResult)
        );
    }
}
