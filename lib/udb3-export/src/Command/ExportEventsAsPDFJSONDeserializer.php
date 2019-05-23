<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class ExportEventsAsPDFJSONDeserializer extends JSONDeserializer
{
    private $defaultSapiVersion;

    /**
     * @param $defaultSapiVersion
     */
    public function __construct($defaultSapiVersion)
    {
        parent::__construct();

        $this->defaultSapiVersion = $defaultSapiVersion;
    }

    /**
     * @param StringLiteral $data
     * @return ExportEventsAsPDF
     */
    public function deserialize(StringLiteral $data)
    {
        $json = parent::deserialize($data);

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        $query = new EventExportQuery($json->query);

        $sapiVersion = $this->defaultSapiVersion;
        if (isset($json->sapiVersion)) {
            $sapiVersion = new SapiVersion($json->sapiVersion);
        }

        if (!isset($json->customizations)) {
            throw new MissingValueException('customizations is missing');
        }

        if (!is_object($json->customizations)) {
            throw new \InvalidArgumentException(
                'customizations should be an object'
            );
        }

        $customizations = $json->customizations;

        if (!isset($customizations->brand)) {
            throw new MissingValueException('brand is missing');
        }

        $brand = $customizations->brand;

        if (!isset($customizations->title)) {
            throw new MissingValueException('title is missing');
        }

        if (!isset($customizations->logo)) {
            throw new MissingValueException('logo is missing');
        }

        $logo = $customizations->logo;

        $title = new Title($customizations->title);

        $template = WebArchiveTemplate::TIPS();
        if (isset($customizations->template)) {
            $template = WebArchiveTemplate::fromNative($customizations->template);
        }

        $command = new ExportEventsAsPDF(
            $query,
            $sapiVersion,
            $brand,
            $logo,
            $title,
            $template
        );

        if (isset($json->email)) {
            $emailAddress = new EmailAddress($json->email);
            $command = $command->withEmailNotificationTo($emailAddress);
        }

        if (isset($json->selection)) {
            $command = $command->withSelection($json->selection);
        }

        if (isset($customizations->subtitle)) {
            $command = $command->withSubtitle(
                new Subtitle($customizations->subtitle)
            );
        }

        if (isset($customizations->footer)) {
            $command = $command->withFooter(
                new Footer($customizations->footer)
            );
        }

        if (isset($customizations->publisher)) {
            $command = $command->withPublisher(
                new Publisher($customizations->publisher)
            );
        }

        return $command;
    }
}
