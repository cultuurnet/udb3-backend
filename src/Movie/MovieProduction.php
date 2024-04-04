<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Movie;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Theme;

final class MovieProduction
{
    private int $mid;

    private Title $title;

    private Description $description;

    private Theme $theme;

    public function __construct(int $mid, Title $title, Description $description, Theme $theme)
    {
        $this->mid = $mid;
        $this->title = $title;
        $this->description = $description;
        $this->theme = $theme;
    }

    public function getMid(): int
    {
        return $this->mid;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }
}
