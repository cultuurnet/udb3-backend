<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\EventExport\FileWriterInterface;
use \Twig_Environment;

class HTMLFileWriter implements FileWriterInterface
{
    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param string $template
     * @param array $variables
     * @param Twig_Environment $twig
     */
    public function __construct(
        $template,
        $variables,
        Twig_Environment $twig = null
    ) {
        $this->template = $template;
        $this->variables = $variables;

        $this->initializeTwig($twig);
    }

    /**
     * @param Twig_Environment|null $twig
     */
    protected function initializeTwig(
        Twig_Environment $twig = null
    ) {
        if (!$twig) {
            $loader = new \Twig_Loader_Filesystem(
                __DIR__ . '/templates'
            );
            $twig = new Twig_Environment($loader);
        }

        $this->setTwig($twig);
    }

    /**
     * @param Twig_Environment $twig
     */
    protected function setTwig(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function write($filePath, $events)
    {
        file_put_contents($filePath, $this->getHTML($events));
    }

    /**
     * @param \Traversable $events
     * @return string
     */
    private function getHTML($events)
    {
        $variables = $this->variables;

        $variables['events'] = $events;

        return $this->twig->render($this->template, $variables);
    }
}
