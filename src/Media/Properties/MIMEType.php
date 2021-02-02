<?php

namespace CultuurNet\UDB3\Media\Properties;

use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\StringLiteral\StringLiteral;
use function is_string;

final class MIMEType extends StringLiteral
{
    protected static $supportedSubtypes = [
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'octet-stream'  => 'application',
    ];

    public static function fromSubtype(string $subtypeString): MIMEType
    {
        if (false === is_string($subtypeString)) {
            throw new InvalidNativeArgumentException($subtypeString, array('string'));
        }

        $typeSupported = array_key_exists($subtypeString, self::$supportedSubtypes);

        if (!$typeSupported) {
            throw new UnsupportedMIMETypeException('MIME type "' . $subtypeString . '" is not supported!');
        }

        $type = self::$supportedSubtypes[$subtypeString];

        return new self($type . '/' . $subtypeString);
    }
}
