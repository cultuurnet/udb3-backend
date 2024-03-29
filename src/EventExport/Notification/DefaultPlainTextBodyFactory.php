<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

class DefaultPlainTextBodyFactory implements BodyFactoryInterface
{
    public function getBodyFor(EventExportResult $eventExportResult): string
    {
        $url = $eventExportResult->getUrl();

        return 'Beste,

        Hierbij vind je de link naar de door jou geëxporteerde documenten uit UiTdatabank: ' . $url . '

        Mocht je vragen hebben, of meer informatie wensen over onze diensten, kan je terecht bij vragen@uitdatabank.be.

        Met vriendelijke groeten,
        Het UiTdatabank team';
    }
}
