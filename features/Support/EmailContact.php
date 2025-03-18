<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

final class EmailContact
{
    private string $name;

    private EmailAddress $address;

    public function __construct(string $name, EmailAddress $address)
    {
        $this->name = $name;
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): EmailAddress
    {
        return $this->address;
    }

    public static function deserialize(array $data): EmailContact
    {
        return new self(
            $data['Name'],
            new EmailAddress($data['Address'])
        );
    }
}
