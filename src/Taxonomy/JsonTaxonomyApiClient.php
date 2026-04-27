<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

final class JsonTaxonomyApiClient implements TaxonomyApiClient
{
    private ?array $terms = null;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $termsEndpoint,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getPlaceTypes(): Categories
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::eventType(), 'places');
    }

    public function getPlaceFacilities(): Categories
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::facility(), 'places');
    }

    public function getEventTypes(): Categories
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::eventType(), 'events');
    }

    public function getEventThemes(): Categories
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::theme(), 'events');
    }

    public function getEventFacilities(): Categories
    {
        return $this->getTermsByDomainAndScope(CategoryDomain::facility(), 'events');
    }

    public function getNativeTerms(): array
    {
        if ($this->terms !== null) {
            return $this->terms;
        }

        $response = $this->client->sendRequest(new Request('GET', $this->termsEndpoint));
        if ($response->getStatusCode() !== 200) {
            $this->logger->error('Taxonomy Api returned non-200 status code', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);
            throw new TaxonomyApiProblem('Taxonomy Api returned non-200 status code.');
        }
        $contents = $response->getBody()->getContents();
        if (empty($contents)) {
            $this->logger->error('Taxonomy Api returned no terms');
            throw new TaxonomyApiProblem('Taxonomy Api returned no terms.');
        }
        $contentsAsJson = Json::decodeAssociatively($contents);
        return $this->terms = $contentsAsJson['terms'];
    }

    private function getTermsByDomainAndScope(CategoryDomain $domain, string $scope): Categories
    {
        $termsByDomainAndScope  = [];
        foreach ($this->getNativeTerms() as $term) {
            if ($term['domain'] === $domain->toString() && in_array($scope, $term['scope'])) {
                $termsByDomainAndScope[] = new Category(new CategoryID($term['id']), new CategoryLabel($term['name']['nl']), $domain);
            }
        }
        return new Categories(...$termsByDomainAndScope);
    }
}
