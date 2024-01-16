<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupDuplicatePlaceWithRedisTest extends TestCase
{
    private const PLACE_ID = 'aadcee95-6180-4924-a8eb-ed829d4957a2';
    private const URI = 'http://example.com/place/';
    private LookupDuplicatePlaceWithRedis $lookupDuplicatePlaceWithRedis;

    /** @var Cache & MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);

        $this->lookupDuplicatePlaceWithRedis = new LookupDuplicatePlaceWithRedis(
            $this->cache,
            new UniqueAddressIdentifierFactory(),
            new Uri(self::URI),
            'current-user-id'
        );
    }

    /**
     * @test
     */
    public function it_returns_duplicate_place_based_on_search_result(): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('online_kerkstraat_1_2000_antwerpen_be_current-user-id')
            ->willReturn(
                self::PLACE_ID
            );

        $duplicatePlaceUri = $this->lookupDuplicatePlaceWithRedis->getDuplicatePlaceUri($this->createPlace());

        $this->assertEquals(self::URI . self::PLACE_ID, $duplicatePlaceUri);
    }

    private function createPlace(): ImmutablePlace
    {
        return new ImmutablePlace(
            new UUID(self::PLACE_ID),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Online')
            ),
            new PermanentCalendar(new OpeningHours()),
            new TranslatedAddress(new Language('nl'), new Udb3Address(
                new Udb3Street('Kerkstraat 1'),
                new Udb3PostalCode('2000'),
                new Udb3Locality('Antwerpen'),
                new CountryCode('BE')
            )),
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                )
            )
        );
    }
}
