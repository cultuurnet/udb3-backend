<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\RdfNamespace;

final class RdfNamespaces
{
    public static function register(): void
    {
        RdfNamespace::set('locn', 'http://www.w3.org/ns/locn#');
    }
}
