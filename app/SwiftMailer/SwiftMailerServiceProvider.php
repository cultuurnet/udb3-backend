<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SwiftMailer;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use RuntimeException;
use Swift_Events_SimpleEventDispatcher;
use Swift_Mailer;
use Swift_StreamFilters_StringReplacementFilterFactory;
use Swift_Transport_Esmtp_Auth_CramMd5Authenticator;
use Swift_Transport_Esmtp_Auth_LoginAuthenticator;
use Swift_Transport_Esmtp_Auth_PlainAuthenticator;
use Swift_Transport_Esmtp_AuthHandler;
use Swift_Transport_EsmtpTransport;
use Swift_Transport_StreamBuffer;

final class SwiftMailerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return ['mailer'];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'mailer',
            static function () use ($container) {
                $transport = new Swift_Transport_EsmtpTransport(
                    new Swift_Transport_StreamBuffer(
                        new Swift_StreamFilters_StringReplacementFilterFactory()
                    ),
                    [
                        new Swift_Transport_Esmtp_AuthHandler(
                            [
                                new Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
                                new Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                                new Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
                            ]
                        ),
                    ],
                    new Swift_Events_SimpleEventDispatcher()
                );

                $options = array_replace(
                    [
                        'host' => 'localhost',
                        'port' => 25,
                        'username' => '',
                        'password' => '',
                        'encryption' => null,
                        'auth_mode' => null,
                    ],
                    $container->get('config')['swiftmailer.options']
                );

                $transport->setHost($options['host']);
                $transport->setPort($options['port']);
                $transport->setEncryption($options['encryption']);

                if (!is_subclass_of($transport, \Swift_SmtpTransport::class)) {
                    throw new RuntimeException('Invalid SMTP transport, received ' . get_class($transport));
                }

                $transport->setUsername($options['username']);
                $transport->setPassword($options['password']);
                $transport->setAuthMode($options['auth_mode']);

                return new Swift_Mailer($transport);
            }
        );
    }
}
