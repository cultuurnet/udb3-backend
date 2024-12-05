<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;

final class PlaceTypeResolver implements TypeResolverInterface
{
    /**
     * @var Category[]
     */
    private array $types;

    public function __construct()
    {
        $this->types = [
            '0.14.0.0.0' => new Category(new CategoryID('0.14.0.0.0'), new CategoryLabel('Monument'), CategoryDomain::eventType()),
            '0.15.0.0.0' => new Category(new CategoryID('0.15.0.0.0'), new CategoryLabel('Natuur, park of tuin'), CategoryDomain::eventType()),
            '3CuHvenJ+EGkcvhXLg9Ykg' => new Category(new CategoryID('3CuHvenJ+EGkcvhXLg9Ykg'), new CategoryLabel('Archeologische Site'), CategoryDomain::eventType()),
            'GnPFp9uvOUyqhOckIFMKmg' => new Category(new CategoryID('GnPFp9uvOUyqhOckIFMKmg'), new CategoryLabel('Museum of galerij'), CategoryDomain::eventType()),
            'kI7uAyn2uUu9VV6Z3uWZTA' => new Category(new CategoryID('kI7uAyn2uUu9VV6Z3uWZTA'), new CategoryLabel('Bibliotheek of documentatiecentrum'), CategoryDomain::eventType()),
            '0.53.0.0.0' => new Category(new CategoryID('0.53.0.0.0'), new CategoryLabel('Recreatiedomein of centrum'), CategoryDomain::eventType()),
            '0.41.0.0.0' => new Category(new CategoryID('0.41.0.0.0'), new CategoryLabel('Thema of pretpark'), CategoryDomain::eventType()),
            'rJRFUqmd6EiqTD4c7HS90w' => new Category(new CategoryID('rJRFUqmd6EiqTD4c7HS90w'), new CategoryLabel('School of onderwijscentrum'), CategoryDomain::eventType()),
            'eBwaUAAhw0ur0Z02i5ttnw' => new Category(new CategoryID('eBwaUAAhw0ur0Z02i5ttnw'), new CategoryLabel('Sportcentrum'), CategoryDomain::eventType()),
            'VRC6HX0Wa063sq98G5ciqw' => new Category(new CategoryID('VRC6HX0Wa063sq98G5ciqw'), new CategoryLabel('Winkel'), CategoryDomain::eventType()),
            'JCjA0i5COUmdjMwcyjNAFA' => new Category(new CategoryID('JCjA0i5COUmdjMwcyjNAFA'), new CategoryLabel('Jeugdhuis of jeugdcentrum'), CategoryDomain::eventType()),
            'Yf4aZBfsUEu2NsQqsprngw' => new Category(new CategoryID('Yf4aZBfsUEu2NsQqsprngw'), new CategoryLabel('Cultuur- of ontmoetingscentrum'), CategoryDomain::eventType()),
            'YVBc8KVdrU6XfTNvhMYUpg' => new Category(new CategoryID('YVBc8KVdrU6XfTNvhMYUpg'), new CategoryLabel('Discotheek'), CategoryDomain::eventType()),
            'BtVNd33sR0WntjALVbyp3w' => new Category(new CategoryID('BtVNd33sR0WntjALVbyp3w'), new CategoryLabel('Bioscoop'), CategoryDomain::eventType()),
            'ekdc4ATGoUitCa0e6me6xA' => new Category(new CategoryID('ekdc4ATGoUitCa0e6me6xA'), new CategoryLabel('Horeca'), CategoryDomain::eventType()),
            'OyaPaf64AEmEAYXHeLMAtA' => new Category(new CategoryID('OyaPaf64AEmEAYXHeLMAtA'), new CategoryLabel('Zaal of expohal'), CategoryDomain::eventType()),
            '0.8.0.0.0' => new Category(new CategoryID('0.8.0.0.0'), new CategoryLabel('Openbare ruimte'), CategoryDomain::eventType()),
            '8.70.0.0.0' => new Category(new CategoryID('8.70.0.0.0'), new CategoryLabel('Theater'), CategoryDomain::eventType()),
            'wwjRVmExI0w6xfQwT1KWpx' => new Category(new CategoryID('wwjRVmExI0w6xfQwT1KWpx'), new CategoryLabel('Speeltuin'), CategoryDomain::eventType()),
        ];
    }

    public function byId(string $typeId): Category
    {
        if (!array_key_exists($typeId, $this->types)) {
            throw new Exception('Unknown place type id: ' . $typeId);
        }
        return $this->types[$typeId];
    }
}
