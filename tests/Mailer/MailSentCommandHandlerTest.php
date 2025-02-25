<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Mailer\Command\SentOwnershipMail;
use CultuurNet\UDB3\Mailer\Ownership\SentOwnershipMailHandler;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MailSentCommandHandlerTest extends TestCase
{
    /** @var Mailer|MockObject */
    private $mailer;

    /** @var MailsSentRepository|MockObject */
    private $mailsSentRepository;
    private SentOwnershipMailHandler $commandHandler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(Mailer::class);
        $this->mailsSentRepository = $this->createMock(MailsSentRepository::class);
        $this->commandHandler = new SentOwnershipMailHandler(
            $this->mailer,
            $this->mailsSentRepository,
            $this->createMock(LoggerInterface::class)
        );
    }

    /** @test */
    public function it_can_sent_mail(): void
    {
        $id = new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');
        $email = new EmailAddress('grotesmurf@publiq.be');
        $subject = 'Mail sent';
        $html = '<p>body</p>';
        $text = 'body';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->willReturn(false);

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('addMailSent')
            ->with(
                $id,
                $email,
                $subject,
            );

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with(
                $email,
                $subject,
                $html,
                $text
            )
            ->willReturn(true);

        $this->commandHandler->handle(new SentOwnershipMail(
            $id,
            $email,
            $subject,
            $html,
            $text
        ));
    }

    /** @test */
    public function it_handles_mail_already_sent(): void
    {
        $id = new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');
        $email = new EmailAddress('grotesmurf@publiq.be');
        $subject = 'Mail sent';
        $html = '<p>body</p>';
        $text = 'body';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->willReturn(true);

        $this->mailsSentRepository
            ->expects($this->never())
            ->method('addMailSent');

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->commandHandler->handle(new SentOwnershipMail(
            $id,
            $email,
            $subject,
            $html,
            $text
        ));
    }

    /** @test */
    public function it_handles_a_failed_sent_mail(): void
    {
        $id = new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e');
        $email = new EmailAddress('grotesmurf@publiq.be');
        $subject = 'Mail sent';
        $html = '<p>body</p>';
        $text = 'body';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->willReturn(false);

        $this->mailsSentRepository
            ->expects($this->never())
            ->method('addMailSent');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with(
                $email,
                $subject,
                $html,
                $text
            )
            ->willReturn(false);

        $this->commandHandler->handle(new SentOwnershipMail(
            $id,
            $email,
            $subject,
            $html,
            $text
        ));
    }
}
