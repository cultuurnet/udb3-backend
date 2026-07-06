<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\EventExport\FileWriterInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HTMLFileWriter implements FileWriterInterface
{
    protected string $template;

    protected array $variables;

    protected ?Environment $twig = null;

    public function __construct(
        string $template,
        array $variables,
        ?Environment $twig = null
    ) {
        $this->template = $template;
        $this->variables = $variables;

        $this->initializeTwig($twig);
    }

    protected function initializeTwig(
        Environment $twig = null
    ): void {
        if (!$twig) {
            $loader = new FilesystemLoader(
                __DIR__ . '/templates'
            );
            $twig = new Environment($loader);
        }

        $this->setTwig($twig);
    }

    protected function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    public function write(string $filePath, \Traversable $events): void
    {
        file_put_contents($filePath, $this->getHTML($events));
    }

    private function getHTML(\Traversable $events): string
    {
        $variables = $this->variables;

        $variables['events'] = $events;

        return $this->twig->render($this->template, $variables);
    }
}
