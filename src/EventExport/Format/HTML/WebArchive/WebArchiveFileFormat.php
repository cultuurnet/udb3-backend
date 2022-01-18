<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;
use Twig_Environment;

abstract class WebArchiveFileFormat
{
    protected HTMLFileWriter $htmlFileWriter;

    public function __construct(
        WebArchiveTemplate $template,
        string $brand,
        string $logo,
        string $title,
        ?string $subtitle = null,
        ?string $footer = null,
        ?string $publisher = null,
        ?Twig_Environment $twig = null
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
        $this->htmlFileWriter = new HTMLFileWriter("export.{$template->toString()}.html.twig", $variables, $twig);
    }

    public function getHTMLFileWriter(): HTMLFileWriter
    {
        return $this->htmlFileWriter;
    }
}
