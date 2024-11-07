<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $status = new Status(
            StatusType::Unavailable(),
            (new TranslatedStatusReason(new Language('nl'), new StatusReason('Het concert van 10/11 is afgelast')))
                ->withTranslation(new Language('fr'), new StatusReason('Le concert de 10/11 a été annulé'))
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
                StatusType::Unavailable(),
                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Het concert van 10/11 is afgelast')))
                    ->withTranslation(new Language('fr'), new StatusReason('Le concert de 10/11 a été annulé'))
            ),
            $actualStatus
        );
    }

    /**
     * @test
     */
    public function it_can_only_hold_one_translation_per_language(): void
    {
        $status = new Status(
            StatusType::Unavailable(),
            (new TranslatedStatusReason(new Language('nl'), new StatusReason('Het concert van 10/11 is afgelast')))
                ->withTranslation(new Language('nl'), new StatusReason('Het concert van 11/11 is afgelast'))
        );

        $this->assertEquals(
            new TranslatedStatusReason(new Language('nl'), new StatusReason('Het concert van 11/11 is afgelast')),
            $status->getReason()
        );
    }

    /**
     * @test
     */
    public function it_can_construct_from_an_udb3_model_status(): void
    {
        $udb3ModelStatus = new Udb3ModelStatus(
            StatusType::Unavailable(),
            new TranslatedStatusReason(
                new Language('nl'),
                new StatusReason('Nederlandse reden')
            )
        );

        $expected = new Status(
            StatusType::Unavailable(),
            new TranslatedStatusReason(new Language('nl'), new StatusReason('Nederlandse reden'))
        );

        $actual = Status::fromUdb3ModelStatus($udb3ModelStatus);

        $this->assertEquals($expected, $actual);
    }
}
