<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\ValueObject;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Theme;

final class ParsedMovie
{
    private string $externalId;

    private Title $title;

    private LocationId $locationId;

    private Theme $theme;

    private Calendar $calendar;

    private PriceInfo $priceInfo;

    private string $imageUrl;

    private ?Description $description = null;

    public function __construct(
        string $externalId,
        Title $title,
        LocationId $locationId,
        Theme $theme,
        Calendar $calendar,
        PriceInfo $priceInfo,
        string $imageUrl
    ) {
        $this->externalId = $externalId;
        $this->locationId = $locationId;
        $this->title = $title;
        $this->theme = $theme;
        $this->calendar = $calendar;
        $this->priceInfo = $priceInfo;
        $this->imageUrl = $imageUrl;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getLocationId(): LocationId
    {
        return $this->locationId;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getPriceInfo(): PriceInfo
    {
        return $this->priceInfo;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getDescription(): ?Description
    {
        return $this->description;
    }

    public function withDescription(Description $description): self
    {
        $c = clone $this;
        $c->description = $description;
        return $c;
    }
}
