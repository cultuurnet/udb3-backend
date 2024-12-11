<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class OnlineLocationPolyfillRequestBodyParser implements RequestBodyParser
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
            $data->location->{'@id'} = $this->iriGenerator->iri(Uuid::NIL);
        }

        return $request->withParsedBody($data);
    }
}
