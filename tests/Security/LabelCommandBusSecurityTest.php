<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand as OfferAbstractLabelCommand;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdateTitle;
use CultuurNet\UDB3\Security\CommandBusSecurity;
use CultuurNet\UDB3\Security\LabelCommandBusSecurity;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelCommandBusSecurityTest extends TestCase
{
    /**
     * @var CommandBusSecurity|MockObject
     */
    private $securityDecoratee;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelReadRepository;

    /**
     * @var LabelCommandBusSecurity
     */
    private $securityWithLabelPrivacy;

    /**
     * @var AddLabel
     */
    private $addLabel;

    protected function setUp()
    {
        $this->securityDecoratee = $this->createMock(CommandBusSecurity::class);

        $this->userId = '82650413-baf2-4257-a25b-d25dc18999dc';

        $this->labelReadRepository = $this->createMock(
            ReadRepositoryInterface::class
        );

        $this->securityWithLabelPrivacy = new LabelCommandBusSecurity(
            $this->securityDecoratee,
            $this->userId,
            $this->labelReadRepository
        );

        $this->addLabel = new AddLabel('6a475eb2-04dd-41e3-95d1-225a1cd511f1', new Label('bibliotheekweek'));
    }

    /**
     * @test
     */
    public function it_delegates_is_authorized_to_decoratee_when_not_label_security_command()
    {
        $translateTitle = new UpdateTitle(
            'cc9b975b-80e3-47db-ae77-8a930e453232',
            new Language('nl'),
            new Title('Hallo wereld')
        );

        $this->securityDecoratee->method('isAuthorized')
            ->with($translateTitle)
            ->willReturn(true);

        $allowed = $this->securityWithLabelPrivacy->isAuthorized($translateTitle);

        $this->assertTrue($allowed);
    }

    /**
     * @test
     */
    public function it_handles_is_authorized_when_label_security_command()
    {
        $labelSecurity = $this->getMockForAbstractClass(
            OfferAbstractLabelCommand::class,
            [
                '6a475eb2-04dd-41e3-95d1-225a1cd511f1',
                new Label('bibliotheekweek'),
            ]
        );

        $this->labelReadRepository->expects($this->once())
            ->method('canUseLabel');

        $this->securityWithLabelPrivacy->isAuthorized($labelSecurity);
    }

    /**
     * @test
     */
    public function a_user_can_only_use_labels_he_is_allowed_to_use()
    {
        $this->labelReadRepository->method('canUseLabel')
            ->willReturn(true);

        $this->assertTrue(
            $this->securityWithLabelPrivacy->isAuthorized($this->addLabel)
        );
    }
}
