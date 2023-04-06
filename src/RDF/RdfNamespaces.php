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
        RdfNamespace::set('udb', 'https://data.publiq.be/ns/uitdatabank#');
        RdfNamespace::set('adms', 'http://www.w3.org/ns/adms#');
        RdfNamespace::set('locn', 'http://www.w3.org/ns/locn#');
        RdfNamespace::set('generiek', 'https://data.vlaanderen.be/ns/generiek/#');
        RdfNamespace::set('geosparql', 'http://www.opengis.net/ont/geosparql#');
    }
}
