<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SmtpMailer implements Mailer
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Address $from;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, Address $from)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->from = $from;
    }

    public function send(EmailAddress $to, string $subject, string $html, string $text): bool
    {
        $email = (new Email())
            ->from($this->from)
            ->to($to->toString())
            ->subject($subject)
            ->text($text)
            ->html($html);

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->critical('[TransportException] ' . $e->getMessage());
            return false;
        }
    }
}
