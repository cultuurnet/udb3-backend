<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class VirtualLocationPolyfillRequestBodyParser implements RequestBodyParser
{
    private IriGeneratorInterface $iriGenerator;

    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        if (!isset($data->location) &&
            isset($data->attendanceMode) &&
            $data->attendanceMode === AttendanceMode::online()->toString()
        ) {
            $data->location = new stdClass();
            $data->location->{'@id'} = $this->iriGenerator->iri(LocationId::VIRTUAL_LOCATION);
        }

        return $request->withParsedBody($data);
    }
}
