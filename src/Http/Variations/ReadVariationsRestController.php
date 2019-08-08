<?php

namespace CultuurNet\UDB3\Http\Variations;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\Hydra\Symfony\PageUrlGenerator;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\JsonLdResponse;
use CultuurNet\UDB3\Variations\ReadModel\Search\CriteriaFromParameterBagFactory;
use CultuurNet\UDB3\Variations\ReadModel\Search\RepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

class ReadVariationsRestController
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var RepositoryInterface
     */
    private $searchRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        RepositoryInterface $searchRepository,
        UrlGenerator $urlGenerator
    ) {
        $this->documentRepository = $documentRepository;
        $this->searchRepository = $searchRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $id
     * @return JsonLdResponse
     */
    public function get($id)
    {
        $variation = $this->documentRepository->get($id);
        return new JsonLdResponse($variation->getRawBody());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $factory = new CriteriaFromParameterBagFactory();
        $criteria = $factory->createCriteriaFromParameterBag($request->query);

        $itemsPerPage = 5;
        $pageNumber = intval($request->query->get('page', 0));

        $variationIds = $this->searchRepository->getOfferVariations(
            $criteria,
            $itemsPerPage,
            $pageNumber
        );

        $variations = [];
        foreach ($variationIds as $variationId) {
            $document = $this->documentRepository->get($variationId);

            if ($document) {
                $variations[] = $document->getBody();
            }
        }

        $totalItems = $this->searchRepository->countOfferVariations(
            $criteria
        );

        $pageUrlFactory = new PageUrlGenerator(
            $request->query,
            $this->urlGenerator,
            'variations',
            'page'
        );

        return new JsonResponse(
            new PagedCollection(
                $pageNumber,
                $itemsPerPage,
                $variations,
                $totalItems,
                $pageUrlFactory,
                true
            )
        );
    }
}
