<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\OfferType;
use RuntimeException;

final class CategoryNotFound extends RuntimeException
{
    public static function withIdInDomain(
        CategoryID $id,
        CategoryDomain $domain
    ): CategoryNotFound {
        return new self(
            sprintf(
                'Category with id %s not found in %s domain.',
                $id->toString(),
                $domain->toString()
            )
        );
    }

    public static function withIdInDomainForOfferType(
        CategoryID $id,
        CategoryDomain $domain,
        OfferType $offerType
    ): CategoryNotFound {
        return new self(
            sprintf(
                'Category with id %s not found in %s domain or not applicable for %s.',
                $id->toString(),
                $domain->toString(),
                $offerType->toString()
            )
        );
    }
}
