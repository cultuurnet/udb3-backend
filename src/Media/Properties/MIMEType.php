<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

use InvalidArgumentException;
use function is_string;

final class MIMEType
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }
    private static array $supportedSubtypes = [
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'octet-stream'  => 'application',
    ];

    public static function fromSubtype(string $subtypeString): MIMEType
    {
        if (!is_string($subtypeString)) {
            throw new InvalidArgumentException($subtypeString);
        }

        $typeSupported = array_key_exists($subtypeString, self::$supportedSubtypes);

        if (!$typeSupported) {
            throw new UnsupportedMIMETypeException('MIME type "' . $subtypeString . '" is not supported!');
        }

        $type = self::$supportedSubtypes[$subtypeString];

        return new self($type . '/' . $subtypeString);
    }
}
