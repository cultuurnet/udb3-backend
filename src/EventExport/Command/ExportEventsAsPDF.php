<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsPDF implements ExportEventsInterface
{
    /**
     * @var EventExportQuery
     */
    private $query;

    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    /**
     * @var null|EmailAddress
     */
    private $address;

    /**
     * @var string[]
     */
    private $selection;

    /**
     * @var string
     */
    private $brand;

    /**
     * @var string
     */
    private $logo;

    /**
     * @var string
     */
    private $title;

    /**
     * @var Subtitle
     */
    private $subtitle;

    /**
     * @var Footer
     */
    private $footer;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var string
     */
    private $template;

    /**
     * @param EventExportQuery $query
     * @param SapiVersion $sapiVersion
     * @param string $brand
     * @param string $logo
     * @param Title $title
     * @param WebArchiveTemplate $template
     */
    public function __construct(
        EventExportQuery $query,
        SapiVersion $sapiVersion,
        string $brand,
        string $logo,
        Title $title,
        WebArchiveTemplate $template
    ) {
        $this->brand = $brand;
        $this->sapiVersion = $sapiVersion;
        $this->logo = $logo;
        $this->query = $query;
        $this->title = $title;
        $this->template = $template->toNative();
    }

    /**
     * @param EmailAddress $address
     * @return ExportEventsAsPDF
     */
    public function withEmailNotificationTo(EmailAddress $address)
    {
        $exportEvents = clone $this;
        $exportEvents->setAddress($address);
        return $exportEvents;
    }

    /**
     * @param EmailAddress $address
     */
    private function setAddress(EmailAddress $address)
    {
        $this->address = $address;
    }

    /**
     * @param $selection
     * @return ExportEventsAsPDF
     */
    public function withSelection($selection)
    {
        $exportEvents = clone $this;
        $exportEvents->setSelection($selection);

        return $exportEvents;
    }

    /**
     * @param string[] $selection
     */
    private function setSelection($selection)
    {
        $this->selection = $selection;
    }

    /**
     * @param Subtitle $subtitle
     * @return ExportEventsAsPDF
     */
    public function withSubtitle(Subtitle $subtitle)
    {

        $exportEvents = clone $this;
        $exportEvents->setSubtitle($subtitle);

        return $exportEvents;
    }

    /**
     * @param Subtitle $subtitle
     */
    private function setSubtitle(Subtitle $subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @param Footer $footer
     * @return ExportEventsAsPDF
     */
    public function withFooter(Footer $footer)
    {
        $exportEvents = clone $this;
        $exportEvents->setFooter($footer);

        return $exportEvents;
    }

    /**
     * @param Publisher $publisher
     * @return ExportEventsAsPDF
     */
    public function withPublisher(Publisher $publisher)
    {
        $exportEvents = clone $this;
        $exportEvents->setPublisher($publisher);

        return $exportEvents;
    }

    /**
     * @param Publisher $publisher
     */
    private function setPublisher(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param Footer $footer
     */
    private function setFooter(Footer $footer)
    {
        $this->footer = $footer;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return Subtitle
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @return Footer
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @return Publisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @return WebArchiveTemplate
     */
    public function getTemplate()
    {
        return WebArchiveTemplate::fromNative($this->template);
    }

    /**
     * @return EventExportQuery The query.
     */
    public function getQuery(): EventExportQuery
    {
        return $this->query;
    }

    /**
     * @return SapiVersion
     */
    public function getSapiVersion(): SapiVersion
    {
        return $this->sapiVersion;
    }

    /**
     * @return null|EmailAddress
     */
    public function getAddress(): ?EmailAddress
    {
        return $this->address;
    }

    /**
     * @return null|\string[]
     */
    public function getSelection(): ?array
    {
        return $this->selection;
    }
}
