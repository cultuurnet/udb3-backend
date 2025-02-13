<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class SmtpMailer implements Mailer
{
    private TwigEnvironment $twig;
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Address $from;
    /** @var string[] */
    private array $whiteListedDomains;

    public function __construct(TwigEnvironment $twig, MailerInterface $mailer, LoggerInterface $logger, Address $from, array $whiteListedDomains)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->from = $from;
        $this->whiteListedDomains = $whiteListedDomains;
    }

    public function send(EmailAddress $to, string $subject, string $htmlTemplate, string $textTemplate, array $variables = []): bool
    {
        try {
            $html = $this->twig->render($htmlTemplate, $variables);
            $text = $this->twig->render($textTemplate, $variables);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            $this->logger->error('[TwigTemplate] ' . $e->getMessage());
            return false;
        }

        $email = (new Email())
            ->from($this->from)
            ->to($to->toString())
            ->subject($subject)
            ->text($text)
            ->html($html);

        try {
            if ($this->isAllowedToSentEmail($to)) {
                $this->mailer->send($email);
            }
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->critical('[TransportException] ' . $e->getMessage());
            return false;
        }
    }

    /*
     * Checks if whitelisted domains are configured, in which case only mails to those domains can be sent.
     * This is used on acc / test.
     * If empty, on production, it will send mails to everybody.
     * */
    private function isAllowedToSentEmail(EmailAddress $email): bool
    {
        if ($this->whiteListedDomains === []) {
            return true;
        }

        foreach ($this->whiteListedDomains as $domain) {
            if (str_ends_with($email->toString(), $domain)) {
                return true;
            }
        }

        return false;
    }
}
