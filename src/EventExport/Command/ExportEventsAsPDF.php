<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsPDF implements ExportEventsInterface
{
    /**
     * @var EventExportQuery
     */
    private $query;

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
     * @var Title
     */
    private $title;

    private ?Subtitle $subtitle;

    private ?Footer $footer;

    private ?Publisher $publisher;


    public function __construct(
        EventExportQuery $query,
        string $brand,
        string $logo,
        Title $title,
        WebArchiveTemplate $template
    ) {
        $this->brand = $brand;
        $this->logo = $logo;
        $this->query = $query;
        $this->title = $title;
        $this->template = $template->toString();
        $this->selection = null;
        $this->subtitle = null;
        $this->footer = null;
        $this->publisher = null;
    }

    /**
     * @return ExportEventsAsPDF
     */
    public function withEmailNotificationTo(EmailAddress $address)
    {
        $exportEvents = clone $this;
        $exportEvents->setAddress($address);
        return $exportEvents;
    }


    private function setAddress(EmailAddress $address)
    {
        $this->address = $address;
    }

    /**
     * @param string[] $selection
     * @return ExportEventsAsPDF
     */
    public function withSelection(array $selection)
    {
        $exportEvents = clone $this;
        $exportEvents->setSelection($selection);

        return $exportEvents;
    }

    /**
     * @param string[] $selection
     */
    private function setSelection(array $selection)
    {
        $this->selection = $selection;
    }

    public function withSubtitle(Subtitle $subtitle): ExportEventsAsPDF
    {
        $exportEvents = clone $this;
        $exportEvents->setSubtitle($subtitle);

        return $exportEvents;
    }

    private function setSubtitle(Subtitle $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return ExportEventsAsPDF
     */
    public function withFooter(Footer $footer)
    {
        $exportEvents = clone $this;
        $exportEvents->setFooter($footer);

        return $exportEvents;
    }

    /**
     * @return ExportEventsAsPDF
     */
    public function withPublisher(Publisher $publisher)
    {
        $exportEvents = clone $this;
        $exportEvents->setPublisher($publisher);

        return $exportEvents;
    }

    private function setPublisher(Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }


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

    public function getSubtitle(): ?Subtitle
    {
        return $this->subtitle;
    }

    public function getFooter(): ?Footer
    {
        return $this->footer;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    /**
     * @return WebArchiveTemplate
     */
    public function getTemplate()
    {
        return new WebArchiveTemplate($this->template);
    }

    /**
     * @return EventExportQuery The query.
     */
    public function getQuery(): EventExportQuery
    {
        return $this->query;
    }


    public function getAddress(): ?EmailAddress
    {
        return $this->address;
    }

    /**
     * @return null|string[]
     */
    public function getSelection(): ?array
    {
        return $this->selection;
    }
}
