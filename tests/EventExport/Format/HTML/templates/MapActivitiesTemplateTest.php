<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\templates;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class MapActivitiesTemplateTest extends TestCase
{
    private const TEMPLATE = 'export.map.activities.html.twig';

    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../../../../src/EventExport/Format/HTML/templates')
        );

        $this->twig->addFunction(
            new TwigFunction('googleMapUrl', static fn (): string => 'http://maps.example.test')
        );
    }

    /**
     * @test
     */
    public function it_does_not_truncate_a_description_shorter_than_75_characters(): void
    {
        $description = 'A short description.';

        $html = $this->renderWithDescription($description);

        $this->assertStringContainsString('<p>' . $description . '</p>', $html);
        $this->assertStringNotContainsString('...', $html);
    }

    /**
     * @test
     */
    public function it_does_not_truncate_a_description_of_exactly_75_characters(): void
    {
        $description = str_repeat('a', 75);

        $html = $this->renderWithDescription($description);

        $this->assertStringContainsString('<p>' . $description . '</p>', $html);
        $this->assertStringNotContainsString('...', $html);
    }

    /**
     * @test
     */
    public function it_truncates_a_description_longer_than_75_characters_and_appends_an_ellipsis(): void
    {
        $description = str_repeat('a', 75) . 'this-part-should-be-cut';

        $html = $this->renderWithDescription($description);

        $this->assertStringContainsString('<p>' . str_repeat('a', 75) . '...</p>', $html);
        $this->assertStringNotContainsString('this-part-should-be-cut', $html);
    }

    /**
     * @test
     */
    public function it_preserves_multibyte_characters_in_the_first_75_characters(): void
    {
        $description = str_repeat('é', 75) . 'tail';

        $html = $this->renderWithDescription($description);

        $this->assertStringContainsString('<p>' . str_repeat('é', 75) . '...</p>', $html);
        $this->assertStringNotContainsString('tail', $html);
    }

    /**
     * @test
     */
    public function it_shows_the_age_range_under_the_price(): void
    {
        $html = $this->render(['ageRange' => 'Geschikt voor 6 tot 12 jaar']);

        $this->assertStringContainsString('<i class="fa fa-user"></i>', $html);
        $this->assertStringContainsString('Geschikt voor 6 tot 12 jaar', $html);
        $this->assertLessThan(
            strpos($html, 'Geschikt voor 6 tot 12 jaar'),
            strpos($html, 'Gratis')
        );
    }

    /**
     * @test
     */
    public function it_does_not_show_an_age_range_when_the_event_has_none(): void
    {
        $html = $this->render([]);

        $this->assertStringNotContainsString('fa-user', $html);
    }

    /**
     * @test
     */
    public function it_no_longer_shows_the_age_in_a_circle(): void
    {
        $html = $this->render(['ageRange' => 'Geschikt voor 6 tot 12 jaar']);

        $this->assertStringNotContainsString('activity__leeftijd', $html);
        $this->assertStringNotContainsString('agedFrom', $html);
    }

    private function renderWithDescription(string $description): string
    {
        return $this->render(['description' => $description]);
    }

    private function render(array $eventOverrides): string
    {
        return $this->twig->render(self::TEMPLATE, [
            'events' => [
                $eventOverrides + [
                    'type' => 'Cursus of workshop',
                    'title' => 'Test event',
                    'description' => 'A short description.',
                    'address' => [
                        'name' => 'Cultuurcentrum De Kruisboog',
                        'street' => 'Sint-Jorisplein 20',
                        'postcode' => '3300',
                        'municipality' => 'Tienen',
                    ],
                    'price' => 'Gratis',
                    'dates' => 'ma 02/03/15',
                ],
            ],
        ]);
    }
}
