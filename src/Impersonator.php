<?php

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultuurNet\Auth\TokenCredentials;

class Impersonator
{
    /**
     * @var \CultureFeed_User
     */
    private $user;

    /**
     * @var TokenCredentials|null
     */
    private $tokenCredentials;

    public function __construct()
    {
        $this->user = null;
        $this->tokenCredentials = null;
    }

    /**
     * @return \CultureFeed_User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return TokenCredentials|null
     */
    public function getTokenCredentials()
    {
        return $this->tokenCredentials;
    }

    /**
     * @param Metadata $metadata
     */
    public function impersonate(Metadata $metadata)
    {
        $metadata = $metadata->serialize();

        $this->user = new \CultureFeed_User();
        $this->user->id = $metadata['user_id'];
        $this->user->nick = $metadata['user_nick'];

        // There might still be queued commands without this metadata because
        // it was added later.
        $this->user->mbox = isset($metadata['user_email']) ? $metadata['user_email'] : null;

        $this->tokenCredentials = $metadata['uitid_token_credentials'];
    }
}
