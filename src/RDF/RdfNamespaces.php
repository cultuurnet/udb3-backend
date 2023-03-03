<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use EasyRdf\RdfNamespace;

final class RdfNamespaces
{
    public static function register(): void
    {
        // Delete the initial 'dc' prefix for dcterms, so the other initial 'dcterms' namespace is used instead.
        RdfNamespace::delete('dc');

        // Set custom namespaces.
        RdfNamespace::set('locn', 'http://www.w3.org/ns/locn#');
    }
}
