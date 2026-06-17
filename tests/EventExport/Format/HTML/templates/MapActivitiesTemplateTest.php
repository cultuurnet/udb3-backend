<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\templates;

use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;
use Twig_Environment;
use Twig_Loader_Filesystem;

final class MapActivitiesTemplateTest extends TestCase
{
    private const TEMPLATE = 'export.map.activities.html.twig';

    private Twig_Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Twig_Environment(
            new Twig_Loader_Filesystem(__DIR__ . '/../../../../../src/EventExport/Format/HTML/templates')
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

    private function renderWithDescription(string $description): string
    {
        return $this->twig->render(self::TEMPLATE, [
            'events' => [
                [
                    'type' => 'Cursus of workshop',
                    'title' => 'Test event',
                    'description' => $description,
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
