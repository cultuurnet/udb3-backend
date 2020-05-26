<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class SimilaritySorterTest extends TestCase
{
    /**
     * @var Entity[]
     */
    private $entities;

    /**
     * @var Entity[]
     */
    private $expectedOrder;

    protected function setUp()
    {
        $fruits = [
            'abrikoos',
            'kiwi',
            'banaan',
            'druif',
            'appelsien',
            'mandarijn',
            'meloen',
            'peer',
            'nectarine',
            'appel',
        ];

        foreach ($fruits as $fruit) {
            $this->entities[] = $this->createEntity(new StringLiteral($fruit));
        }

        $expectedFruits = [
            'peer',
            'appel',
            'appelsien',
            'meloen',
            'nectarine',
            'kiwi',
            'druif',
            'banaan',
            'abrikoos',
            'mandarijn',
        ];

        foreach ($expectedFruits as $expectedFruit) {
            $this->expectedOrder[] = $this->createEntity(
                new StringLiteral($expectedFruit)
            );
        }
    }

    /**
     * @test
     */
    public function it_can_sort_best_match()
    {
        $sorter = new SimilaritySorter();
        $value = new StringLiteral('pee');

        $sorted = $sorter->sort($this->entities, $value);

        $this->assertTrue($sorted);
        $this->assertEquals($this->expectedOrder, $this->entities);
    }

    /**
     * @param StringLiteral $name
     * @return Entity
     */
    private function createEntity(StringLiteral $name)
    {
        return new Entity(
            new UUID('b537eab6-3149-11e6-ac61-9e71128cae77'),
            $name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );
    }
}
