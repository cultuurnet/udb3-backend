<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Impersonator
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function impersonate(Metadata $metadata)
    {
        $metadata = $metadata->serialize();

        $this->session->set(
            'culturefeed_user',
            new \CultuurNet\Auth\User(
                $metadata['user_id'],
                $metadata['uitid_token_credentials']
            )
        );
    }
}
