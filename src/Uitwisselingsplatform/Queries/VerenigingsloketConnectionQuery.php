<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Uitwisselingsplatform\Queries;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class VerenigingsloketConnectionQuery implements SparqlQueryInterface
{
    private string $organizerUrl;

    public function __construct(Uuid $organizerId)
    {
        $this->organizerUrl = 'https://data.publiq.be/id/organizer/udb/' . $organizerId->toString();
    }

    public function getQuery(): string
    {
        return '
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX dcterms: <http://purl.org/dc/terms/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX sssom: <https://w3id.org/sssom/>
            PREFIX adms: <http://www.w3.org/ns/adms#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

            SELECT DISTINCT ?mapping ?object_id ?object_label ?subject_id ?subject_label ?vcode ?vcode_url
            FROM <https://verenigingsloket.be/mappings/>
            WHERE {
                ?mapping rdf:type sssom:Mapping .
                ?mapping sssom:predicate_id "skos:exactMatch" .
                ?mapping sssom:object_id <' . $this->organizerUrl . '> .
                ?mapping sssom:object_label ?object_label .
                ?mapping sssom:subject_id ?subject_id .
                ?mapping sssom:subject_label ?subject_label .

                # Extract the vcode identifier using string replacement
                BIND(REPLACE(STR(?subject_id), "https://data.vlaanderen.be/id/verenigingen/", "") AS ?vcode)

                # Create the Verenigingsloket URL as a typed literal
                BIND(STRDT(CONCAT("https://www.verenigingsloket.be/nl/verenigingen/", ?vcode), xsd:anyURI) AS ?vcode_url)
            }
            ';
    }

    public function getEndpoint(): string
    {
        return 'https://data.uitwisselingsplatform.be/be.dcjm.verenigingen/verenigingen-entity-mapping/sparql';
    }
}
