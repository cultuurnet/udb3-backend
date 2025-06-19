<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelCommandBusSecurityTest extends TestCase
{
    private CommandBusSecurity&MockObject $securityDecoratee;

    private ReadRepositoryInterface&MockObject $labelReadRepository;

    private LabelCommandBusSecurity $securityWithLabelPrivacy;

    private AddLabel $addLabel;

    protected function setUp(): void
    {
        $this->securityDecoratee = $this->createMock(CommandBusSecurity::class);

        $userId = '82650413-baf2-4257-a25b-d25dc18999dc';

        $this->labelReadRepository = $this->createMock(
            ReadRepositoryInterface::class
        );

        $this->securityWithLabelPrivacy = new LabelCommandBusSecurity(
            $this->securityDecoratee,
            $userId,
            $this->labelReadRepository
        );

        $this->addLabel = new AddLabel('6a475eb2-04dd-41e3-95d1-225a1cd511f1', new Label(new LabelName('bibliotheekweek')));
    }

    /**
     * @test
     */
    public function it_delegates_is_authorized_to_decoratee_when_not_label_security_command(): void
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
    public function a_user_can_only_use_labels_he_is_allowed_to_use(): void
    {
        $this->labelReadRepository->method('canUseLabel')
            ->willReturn(true);

        $this->assertTrue(
            $this->securityWithLabelPrivacy->isAuthorized($this->addLabel)
        );
    }
}
