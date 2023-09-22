<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\TestCase;

class EventThemeResolverTest extends TestCase
{
    /**
     * @var EventThemeResolver
     */
    private $themeResolver;

    public function setUp(): void
    {
        $this->themeResolver = new EventThemeResolver();
    }

    /**
     * @test
     */
    public function it_should_resolve_themes_by_matching_id(): void
    {
        $resolvedTheme = $this->themeResolver->byId('0.52.0.0.0');
        $expectedTheme = new Theme('0.52.0.0.0', 'Circus');

        $this->assertEquals($expectedTheme, $resolvedTheme);
    }

    /**
     * @test
     */
    public function it_should_not_resolve_a_theme_when_id_is_unknown(): void
    {
        $this->expectExceptionMessage('Unknown event theme id: 182.0.0.1');
        $this->themeResolver->byId('182.0.0.1');
    }
}
