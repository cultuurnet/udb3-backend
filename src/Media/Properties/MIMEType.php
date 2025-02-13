<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

final class MIMEType
{
    use IsString;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    private static array $supportedSubtypes = [
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'octet-stream'  => 'application',
    ];

    /**
     * @throws InvalidArgumentException
     * @throws UnsupportedMIMETypeException
     */
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

    public function getFilenameExtension(): string
    {
        $parts = explode('/', $this->value);
        return $parts[1];
    }
}
