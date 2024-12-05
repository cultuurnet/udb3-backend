<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class EventTypeResolver implements TypeResolverInterface
{
    /**
     * @var Category[]
     */
    private array $types;

    public function __construct()
    {
        $this->types = [
            '0.7.0.0.0' => new Category(new CategoryID('0.7.0.0.0'), new CategoryLabel('Begeleide rondleiding'), CategoryDomain::eventType()),
            '0.6.0.0.0' => new Category(new CategoryID('0.6.0.0.0'), new CategoryLabel('Beurs'), CategoryDomain::eventType()),
            '0.50.4.0.0' => new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            '0.3.1.0.0' => new Category(new CategoryID('0.3.1.0.0'), new CategoryLabel('Cursus of workshop'), CategoryDomain::eventType()),
            '0.3.1.0.1' => new Category(new CategoryID('0.3.1.0.1'), new CategoryLabel('Cursus met open sessies'), CategoryDomain::eventType()),
            '0.54.0.0.0' => new Category(new CategoryID('0.54.0.0.0'), new CategoryLabel('Dansvoorstelling'), CategoryDomain::eventType()),
            '1.50.0.0.0' => new Category(new CategoryID('1.50.0.0.0'), new CategoryLabel('Eten en drinken'), CategoryDomain::eventType()),
            '0.5.0.0.0' => new Category(new CategoryID('0.5.0.0.0'), new CategoryLabel('Festival'), CategoryDomain::eventType()),
            '0.50.6.0.0' => new Category(new CategoryID('0.50.6.0.0'), new CategoryLabel('Film'), CategoryDomain::eventType()),
            '0.57.0.0.0' => new Category(new CategoryID('0.57.0.0.0'), new CategoryLabel('Kamp of vakantie'), CategoryDomain::eventType()),
            '0.28.0.0.0' => new Category(new CategoryID('0.28.0.0.0'), new CategoryLabel('Kermis of feestelijkheid'), CategoryDomain::eventType()),
            '0.3.2.0.0' => new Category(new CategoryID('0.3.2.0.0'), new CategoryLabel('Lezing of congres'), CategoryDomain::eventType()),
            '0.37.0.0.0' => new Category(new CategoryID('0.37.0.0.0'), new CategoryLabel('Markt of braderie'), CategoryDomain::eventType()),
            '0.12.0.0.0' => new Category(new CategoryID('0.12.0.0.0'), new CategoryLabel('Opendeurdag'), CategoryDomain::eventType()),
            '0.49.0.0.0' => new Category(new CategoryID('0.49.0.0.0'), new CategoryLabel('Party of fuif'), CategoryDomain::eventType()),
            '0.17.0.0.0' => new Category(new CategoryID('0.17.0.0.0'), new CategoryLabel('Route'), CategoryDomain::eventType()),
            '0.50.21.0.0' => new Category(new CategoryID('0.50.21.0.0'), new CategoryLabel('Spel of quiz'), CategoryDomain::eventType()),
            '0.59.0.0.0' => new Category(new CategoryID('0.59.0.0.0'), new CategoryLabel('Sport en beweging'), CategoryDomain::eventType()),
            '0.19.0.0.0' => new Category(new CategoryID('0.19.0.0.0'), new CategoryLabel('Sportwedstrijd bekijken'), CategoryDomain::eventType()),
            '0.0.0.0.0' => new Category(new CategoryID('0.0.0.0.0'), new CategoryLabel('Tentoonstelling'), CategoryDomain::eventType()),
            '0.55.0.0.0' => new Category(new CategoryID('0.55.0.0.0'), new CategoryLabel('Theatervoorstelling'), CategoryDomain::eventType()),
            '0.51.0.0.0' => new Category(new CategoryID('0.51.0.0.0'), new CategoryLabel('Type onbepaald'), CategoryDomain::eventType()),
        ];
    }

    public function byId(string $typeId): Category
    {
        if (!array_key_exists($typeId, $this->types)) {
            throw new Exception('Unknown event type id: ' . $typeId);
        }
        return $this->types[$typeId];
    }

    public static function isOnlyAvailableUntilStartDate(Category $eventType): bool
    {
        return in_array(
            $eventType->getId()->toString(),
            [
                '0.3.1.0.0',
                '0.57.0.0.0',
            ]
        );
    }
}
