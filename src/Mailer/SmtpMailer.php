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
    private Address $from;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, Address $from, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->from = $from;
        $this->logger = $logger;
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
