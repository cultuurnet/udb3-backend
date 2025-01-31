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
        RdfNamespace::set('prov', 'http://www.w3.org/ns/prov#');
        RdfNamespace::set('generiek', 'https://data.vlaanderen.be/ns/generiek#');
        RdfNamespace::set('geosparql', 'http://www.opengis.net/ont/geosparql#');
        RdfNamespace::set('cidoc', 'http://www.cidoc-crm.org/cidoc-crm/');
        RdfNamespace::set('m8g', 'http://data.europa.eu/m8g/');
        RdfNamespace::set('cpa', 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.');
        RdfNamespace::set('cpr', 'https://data.vlaanderen.be/ns/cultuurparticipatie#Realisator.');
        RdfNamespace::set('cpp', 'https://data.vlaanderen.be/ns/cultuurparticipatie#Prijsinfo.');
        RdfNamespace::set('cp', 'https://data.vlaanderen.be/ns/cultuurparticipatie#');
        RdfNamespace::set('schema', 'https://schema.org/');
        RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
        RdfNamespace::set('labeltype', 'https://data.cultuurparticipatie.be/id/concept/LabelType/');
        RdfNamespace::set('platform', 'https://data.uitwisselingsplatform.be/ns/platform');
    }
}
