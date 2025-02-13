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
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;

class SmtpMailerTest extends TestCase
{
    private SmtpMailer $smtpMailer;
    /** @var TwigEnvironment|MockObject */
    private $twig;
    /** @var MailerInterface|MockObject */
    private $mailer;
    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(TwigEnvironment::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->smtpMailer = new SmtpMailer(
            $this->twig,
            $this->mailer,
            $this->logger,
            new Address('koen@publiq.be', 'Publiq'),
            ['publiq.be']
        );
    }

    /** @test */
    public function it_handles_successful_email_sending(): void
    {
        $to = new EmailAddress('user@publiq.be');
        $subject = 'Test Subject';
        $htmlTemplate = 'email.html.twig';
        $textTemplate = 'email.txt.twig';
        $variables = ['name' => 'User'];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturn('Rendered Content');

        $this->mailer->expects($this->once())
            ->method('send');

        $result = $this->smtpMailer->send($to, $subject, $htmlTemplate, $textTemplate, $variables);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_twig_rendering_failure(): void
    {
        $to = new EmailAddress('user@publiq.be');
        $subject = 'Test Subject';
        $htmlTemplate = 'email.html.twig';
        $textTemplate = 'email.txt.twig';

        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new LoaderError('Twig error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[TwigTemplate]'));

        $result = $this->smtpMailer->send($to, $subject, $htmlTemplate, $textTemplate);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_email_sending_failure(): void
    {
        $to = new EmailAddress('user@publiq.be');
        $subject = 'Test Subject';
        $htmlTemplate = 'email.html.twig';
        $textTemplate = 'email.txt.twig';
        $variables = ['name' => 'User'];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturn('Rendered Content');

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('[TransportException]'));

        $result = $this->smtpMailer->send($to, $subject, $htmlTemplate, $textTemplate, $variables);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_does_not_sent_mail_to_illegal_domains(): void
    {
        $to = new EmailAddress('sauron@evil.com');
        $subject = 'Test Subject';
        $htmlTemplate = 'email.html.twig';
        $textTemplate = 'email.txt.twig';
        $variables = ['name' => 'User'];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturn('Rendered Content');

        // Do not send the email!
        $this->mailer->expects($this->never())
            ->method('send');

        $result = $this->smtpMailer->send($to, $subject, $htmlTemplate, $textTemplate, $variables);

        $this->assertTrue($result);
    }
}
