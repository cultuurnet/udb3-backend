<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand as OfferAbstractLabelCommand;
use CultuurNet\UDB3\Offer\Mock\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdateTitle;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityWithLabelPrivacyTest extends TestCase
{
    /**
     * @var SecurityInterface|MockObject
     */
    private $securityDecoratee;

    /**
     * @var UserIdentificationInterface|MockObject
     */
    private $userIdentification;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelReadRepository;

    /**
     * @var SecurityWithLabelPrivacy
     */
    private $securityWithLabelPrivacy;

    /**
     * @var AddLabel
     */
    private $addLabel;

    protected function setUp()
    {
        $this->securityDecoratee = $this->createMock(SecurityInterface::class);

        $this->userIdentification = $this->createMock(
            UserIdentificationInterface::class
        );

        $this->labelReadRepository = $this->createMock(
            ReadRepositoryInterface::class
        );

        $this->securityWithLabelPrivacy = new SecurityWithLabelPrivacy(
            $this->securityDecoratee,
            $this->userIdentification,
            $this->labelReadRepository
        );

        $this->addLabel = new AddLabel('6a475eb2-04dd-41e3-95d1-225a1cd511f1', new Label('bibliotheekweek'));
    }

    /**
     * @test
     */
    public function it_delegates_allows_update_with_cdbxml_to_decoratee()
    {
        $offerId = new StringLiteral('3650cf00-aa8a-4cf3-a928-a01c2eb3b0d8');

        $this->securityDecoratee->method('allowsUpdateWithCdbXml')
            ->with($offerId);

        $this->securityWithLabelPrivacy->allowsUpdateWithCdbXml($offerId);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_delegates_is_authorized_to_decoratee_when_not_label_security_command()
    {
        $translateTitle = new UpdateTitle(
            'cc9b975b-80e3-47db-ae77-8a930e453232',
            new Language('nl'),
            new StringLiteral('Hallo wereld')
        );

        $this->securityDecoratee->method('isAuthorized')
            ->with($translateTitle);

        $this->securityWithLabelPrivacy->isAuthorized($translateTitle);

        $this->expectNotToPerformAssertions();
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

        $this->userIdentification->method('getId')
            ->willReturn(new StringLiteral('82650413-baf2-4257-a25b-d25dc18999dc'));

        $this->labelReadRepository->expects($this->once())
            ->method('canUseLabel');

        $this->securityWithLabelPrivacy->isAuthorized($labelSecurity);
    }

    /**
     * @test
     */
    public function a_user_can_only_use_labels_he_is_allowed_to_use()
    {
        $this->userIdentification->method('getId')
            ->willReturn(new StringLiteral('82650413-baf2-4257-a25b-d25dc18999dc'));

        $this->labelReadRepository->method('canUseLabel')
            ->willReturn(true);

        $this->assertTrue(
            $this->securityWithLabelPrivacy->isAuthorized($this->addLabel)
        );
    }
}
