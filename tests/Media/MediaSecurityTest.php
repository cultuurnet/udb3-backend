<?php

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\SecurityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class MediaSecurityTest extends TestCase
{
    /**
     * @var SecurityInterface|MockObject
     */
    private $baseSecurity;

    /**
     * @var MediaSecurity|MockObject
     */
    private $mediaSecurity;

    public function setUp()
    {
        $this->baseSecurity = $this->createMock(SecurityInterface::class);
        $this->mediaSecurity = new MediaSecurity($this->baseSecurity);
    }

    /**
     * @test
     */
    public function it_should_always_authorize_command_with_media_upload_permission()
    {
        $command = new UploadImage(
            UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            StringLiteral::fromNative('description'),
            StringLiteral::fromNative('copyright'),
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $authorized = $this->mediaSecurity->isAuthorized($command);

        $this->assertEquals(true, $authorized);
    }

    /**
     * @test
     */
    public function it_should_delegate_authorization_of_non_media_commands_to_the_decorated_security()
    {
        /** @var AuthorizableCommandInterface|MockObject $command */
        $command = $this->createMock(AuthorizableCommandInterface::class);
        $command
            ->expects($this->once())
            ->method('getPermission')
            ->willReturn(Permission::GEBRUIKERS_BEHEREN());

        $this->baseSecurity
            ->expects($this->once())
            ->method('isAuthorized')
            ->with($command);

        $this->mediaSecurity->isAuthorized($command);
    }
}
