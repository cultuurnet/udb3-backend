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
    private EventExportQuery $query;

    private ?EmailAddress $address;

    /**
     * @var null|string[]
     */
    private ?array $selection;

    private string $brand;

    private string $logo;

    private Title $title;

    private ?Subtitle $subtitle;

    private ?Footer $footer;

    private ?Publisher $publisher;

    private string $template;

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

    public function withEmailNotificationTo(EmailAddress $address): ExportEventsAsPDF
    {
        $exportEvents = clone $this;
        $exportEvents->setAddress($address);
        return $exportEvents;
    }


    private function setAddress(EmailAddress $address): void
    {
        $this->address = $address;
    }

    /**
     * @param string[] $selection
     */
    public function withSelection(array $selection): ExportEventsAsPDF
    {
        $exportEvents = clone $this;
        $exportEvents->setSelection($selection);

        return $exportEvents;
    }

    /**
     * @param string[] $selection
     */
    private function setSelection(array $selection): void
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

    public function withFooter(Footer $footer): ExportEventsAsPDF
    {
        $exportEvents = clone $this;
        $exportEvents->setFooter($footer);

        return $exportEvents;
    }

    public function withPublisher(Publisher $publisher): ExportEventsAsPDF
    {
        $exportEvents = clone $this;
        $exportEvents->setPublisher($publisher);

        return $exportEvents;
    }

    private function setPublisher(Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    private function setFooter(Footer $footer): void
    {
        $this->footer = $footer;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function getTitle(): Title
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

    public function getTemplate(): WebArchiveTemplate
    {
        return new WebArchiveTemplate($this->template);
    }

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
