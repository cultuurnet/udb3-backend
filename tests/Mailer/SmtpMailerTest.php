<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SmtpMailerTest extends TestCase
{
    private SmtpMailer $smtpMailer;
    /** @var MailerInterface|MockObject */
    private $mailer;
    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->smtpMailer = new SmtpMailer(
            $this->mailer,
            $this->logger,
            new Address('koen@publiq.be', 'Publiq'),
        );
    }

    /** @test */
    public function it_handles_successful_email_sending(): void
    {
        $to = new EmailAddress('user@publiq.be');
        $subject = 'Test Subject';
        $html = 'My email';
        $text = 'My email';

        $this->mailer->expects($this->once())
            ->method('send');

        $result = $this->smtpMailer->send($to, $subject, $html, $text);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_email_sending_failure(): void
    {
        $to = new EmailAddress('user@publiq.be');
        $subject = 'Test Subject';
        $html = 'My email';
        $text = 'My email';

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('[TransportException]'));

        $result = $this->smtpMailer->send($to, $subject, $html, $text);

        $this->assertFalse($result);
    }
}
