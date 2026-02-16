<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

final class JsonTaxonomyApiClient implements TaxonomyApiClient
{
    private array $terms;

    public function __construct(
        readonly ClientInterface $client,
        readonly string $termsEndpoint
    ) {
        $request = new Request(
            'GET',
            $this->termsEndpoint,
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);
        $this->terms = $contents['terms'];
    }

    public function getPlaceTypes(): array
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::eventType(), 'places');
    }

    public function getPlaceFacilities(): array
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::facility(), 'places');
    }

    public function getEventTypes(): array
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::eventType(), 'events');
    }

    public function getEventThemes(): array
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::theme(), 'events');
    }

    public function getEventFacilities(): array
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::facility(), 'events');
    }

    private function getTermsByDomainAndScope(CategoryDomain $domain, string $scope): array
    {
        $termsByDomainAndScope  = [];
        foreach ($this->terms as $term) {
            if ($term['domain'] === $domain->toString() && in_array($scope, $term['scope'])) {
                $termsByDomainAndScope[] = $term;
                $termsByDomainAndScope[$term['id']] = new Category(new CategoryID($term['id']), new CategoryLabel($term['name']['nl']), $domain);
            }
        }
        return $termsByDomainAndScope;
    }
}
