<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class ImportTermRequestBodyParser implements RequestBodyParser
{
    private CategoryResolverInterface $placeCategoryResolver;

    public function __construct(CategoryResolverInterface $placeCategoryResolver)
    {
        $this->placeCategoryResolver = $placeCategoryResolver;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();

        // Attempt to add label and/or domain to terms, or fix them if they're incorrect.
        if (isset($json->terms) && is_array($json->terms)) {
            $json->terms = array_map(
                function (stdClass $term) {
                    if (isset($term->id) && is_string($term->id)) {
                        $id = $term->id;
                        $category = $this->placeCategoryResolver->byId(new CategoryID($id));

                        if ($category) {
                            $term->label = $category->getLabel()->toString();
                            $term->domain = $category->getDomain()->toString();
                        }
                    }

                    return $term;
                },
                $json->terms
            );
        }

        return $request->withParsedBody($json);
    }
}
