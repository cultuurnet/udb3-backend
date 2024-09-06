<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\EmptyCategoryId;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class ImportTermRequestBodyParser implements RequestBodyParser
{
    private CategoryResolverInterface $categoryResolver;

    public function __construct(CategoryResolverInterface $categoryResolver)
    {
        $this->categoryResolver = $categoryResolver;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();

        // Taken from https://taxonomy.uitdatabank.be/api/domain (without eventtype, theme, facility!)
        $ignoreDomains = [
            'municipal',
            'IPE',
            'actortype',
            'educationfield',
            'educationlevel',
            'flandersregion',
            'flanderstouristregion',
            'misc',
            'publicscope',
            'targetaudience',
            'umv',
            'workingregion',
        ];

        if (isset($json->terms) && is_array($json->terms)) {
            // Filter out terms from ignored legacy domains.
            // We have events that were imported from XML that had terms from these domains, and they used to be
            // projected to JSON-LD. An integrator might be sending us our own JSON-LD with some modifications, so we
            // need to make sure we do not return an error for these old domains. Easiest is to just filter them out.
            $json->terms = array_filter(
                $json->terms,
                function ($term) use ($ignoreDomains) {
                    return !($term instanceof stdClass && isset($term->domain) && in_array($term->domain, $ignoreDomains, true));
                }
            );

            // Attempt to add label and/or domain to terms, or fix them if they're incorrect.
            $json->terms = array_map(
                function ($term, int $index) {
                    if ($term instanceof stdClass && isset($term->id) && is_string($term->id)) {
                        $id = $term->id;
                        try {
                            $category = $this->categoryResolver->byId(new CategoryID($id));
                        } catch (EmptyCategoryId $exception) {
                            $category = null;
                        }
                        if ($category) {
                            $term->label = $category->getLabel()->toString();
                            $term->domain = $category->getDomain()->toString();
                        }

                        if ($category === null) {
                            throw ApiProblem::bodyInvalidData(
                                new SchemaError(
                                    '/terms/' . $index . '/id',
                                    'The term ' . $id . ' does not exist or is not supported'
                                )
                            );
                        }
                    }

                    return $term;
                },
                $json->terms,
                array_keys($json->terms)
            );
        }

        return $request->withParsedBody($json);
    }
}
