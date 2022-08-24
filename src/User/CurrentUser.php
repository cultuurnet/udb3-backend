<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

final class CurrentUser
{
    private ?string $id;
    private bool $isGodUser;

    public function __construct(?string $id, bool $isGodUser)
    {
        $this->id = $id;
        $this->isGodUser = $isGodUser;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isGodUser(): bool
    {
        return $this->isGodUser;
    }

    public function isAnonymous(): bool
    {
        return $this->id === null;
    }
}
