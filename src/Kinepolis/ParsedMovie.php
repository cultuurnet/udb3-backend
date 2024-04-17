<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

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

    private Description $description;

    private Theme $theme;

    private Calendar $calendar;

    private PriceInfo $priceInfo;

    public function __construct(
        string $externalId,
        Title $title,
        LocationId $locationId,
        Description $description,
        Theme $theme,
        Calendar $calendar,
        PriceInfo $priceInfo
    ) {
        $this->externalId = $externalId;
        $this->locationId = $locationId;
        $this->title = $title;
        $this->description = $description;
        $this->theme = $theme;
        $this->calendar = $calendar;
        $this->priceInfo = $priceInfo;
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

    public function getDescription(): Description
    {
        return $this->description;
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
}
