<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Notification;

use CultuurNet\UDB3\EventExport\EventExportResult;

class DefaultHTMLBodyFactory implements BodyFactoryInterface
{
    public function getBodyFor(EventExportResult $eventExportResult): string
    {
        $url = $eventExportResult->getUrl();

        /* @codingStandardsIgnoreStart */
        return '<p>Beste, <br /><br />Hierbij vind je de link naar de door jou geëxporteerde documenten uit UiTdatabank: <a href="' . $url . '">' . $url . '</a><br /><br />
        Mocht je vragen hebben, of meer informatie wensen over onze diensten, kan je terecht bij vragen@uitdatabank.be.<br /><br />
        Met vriendelijke groeten,<br />Het UiTdatabank team</p>';
        /* @codingStandardsIgnoreEnd */
    }
}
