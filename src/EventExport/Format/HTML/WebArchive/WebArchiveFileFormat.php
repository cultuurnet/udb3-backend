<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;
use Twig_Environment;

abstract class WebArchiveFileFormat
{
    /**
     * @var HTMLFileWriter
     */
    protected $htmlFileWriter;

    /**
     * @param string                $brand
     * @param string                $logo
     * @param string                $title
     * @param string|null           $subtitle
     * @param string|null           $footer
     * @param string|null           $publisher
     */
    public function __construct(
        WebArchiveTemplate $template,
        $brand,
        $logo,
        $title,
        $subtitle = null,
        $footer = null,
        $publisher = null,
        Twig_Environment $twig = null
    ) {
        $variables = [
            'brand' => $brand,
            'logo' => $logo,
            'title' => $title,
            'subtitle' => $subtitle,
            'footer' => $footer,
            'publisher' => $publisher,
            'partner' => !in_array($brand, ['uit', 'vlieg', 'uitpas', 'paspartoe']),
        ];
        $this->htmlFileWriter = new HTMLFileWriter("export.{$template->getValue()}.html.twig", $variables, $twig);
    }

    /**
     * @return HTMLFileWriter
     */
    public function getHTMLFileWriter()
    {
        return $this->htmlFileWriter;
    }
}
