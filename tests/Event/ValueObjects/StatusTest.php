<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason as Udb3ModelStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType as Udb3ModelStatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $status = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het concert van 10/11 is afgelast'),
                new StatusReason(new Language('fr'), 'Le concert de 10/11 a été annulé'),
            ]
        );

        $this->assertEquals(
            [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'Het concert van 10/11 is afgelast',
                    'fr' => 'Le concert de 10/11 a été annulé',
                ],
            ],
            $status->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $actualStatus = Status::deserialize(
            [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'Het concert van 10/11 is afgelast',
                    'fr' => 'Le concert de 10/11 a été annulé',
                ],
            ]
        );

        $this->assertEquals(
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Het concert van 10/11 is afgelast'),
                    new StatusReason(new Language('fr'), 'Le concert de 10/11 a été annulé'),
                ]
            ),
            $actualStatus
        );
    }

    /**
     * @test
     */
    public function it_can_only_hold_one_translation_per_language(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het concert van 10/11 is afgelast'),
                new StatusReason(new Language('nl'), 'Het concert van 10/11 is stiekem toch niet afgelast'),
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_construct_from_an_udb3_model_status(): void
    {
        $udb3ModelStatus = new Udb3ModelStatus(
            Udb3ModelStatusType::Unavailable(),
            new TranslatedStatusReason(
                new Udb3ModelLanguage('nl'),
                new Udb3ModelStatusReason('Nederlandse reden')
            )
        );

        $expected = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Nederlandse reden'),
            ]
        );

        $actual = Status::fromUdb3ModelStatus($udb3ModelStatus);

        $this->assertEquals($expected, $actual);
    }
}
