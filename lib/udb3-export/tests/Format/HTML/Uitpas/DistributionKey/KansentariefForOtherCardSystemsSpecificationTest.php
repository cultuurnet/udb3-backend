<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey;
use CultureFeed_Uitpas_DistributionKey_Condition as Condition;

class KansentariefForOtherCardSystemsSpecificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KansentariefForOtherCardSystemsSpecification
     */
    protected $specification;

    public function setUp()
    {
        $this->specification = new KansentariefForOtherCardSystemsSpecification();
    }

    public function satisfyingDistributionKeysProvider()
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        $data = [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
                        ),
                    ]
                )
            ],
            [
                $keyFactory->buildKey(
                    '0.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
                        ),
                    ]
                )
            ],
            [
                $keyFactory->buildKey(
                    '0.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
                        ),
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            '7'
                        ),
                    ]
                )
            ],
        ];

        return $data;
    }

    /**
     * @test
     * @dataProvider satisfyingDistributionKeysProvider
     * @param CultureFeed_Uitpas_DistributionKey $key
     */
    public function it_is_satisfied_by_a_kansarm_in_at_least_one_cardsystem_condition(
        CultureFeed_Uitpas_DistributionKey $key
    ) {
        $this->assertTrue(
            $this->specification->isSatisfiedBy($key)
        );
    }

    /**
     * @return array
     */
    public function nonSatisfyingDistributionKeysProvider()
    {
        $conditionFactory = new DistributionKeyConditionFactory();
        $keyFactory = new DistributionKeyFactory();

        $data = [
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_MY_CARDSYSTEM
                        ),
                    ]
                )
            ],
            [
                $keyFactory->buildKey(
                    '0.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_MY_CARDSYSTEM
                        ),
                    ]
                )
            ],
            [
                $keyFactory->buildKey(
                    '1.0',
                    [
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_KANSARM,
                            Condition::OPERATOR_IN,
                            Condition::VALUE_MY_CARDSYSTEM
                        ),
                        $conditionFactory->buildCondition(
                            Condition::DEFINITION_PRICE,
                            Condition::OPERATOR_LESS_THAN,
                            7
                        ),
                    ]
                ),
            ]
        ];

        return $data;
    }

    /**
     * @test
     * @dataProvider nonSatisfyingDistributionKeysProvider
     * @param CultureFeed_Uitpas_DistributionKey $key
     */
    public function it_is_not_satisfied_by_other_distribution_key_conditions(
        CultureFeed_Uitpas_DistributionKey $key
    ) {
        $this->assertFalse(
            $this->specification->isSatisfiedBy($key)
        );
    }
}
