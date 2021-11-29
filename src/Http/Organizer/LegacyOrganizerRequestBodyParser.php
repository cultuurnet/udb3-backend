<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyOrganizerRequestBodyParser implements RequestBodyParser
{
    private UuidGeneratorInterface $uuidGenerator;

    private IriGeneratorInterface $iriGenerator;

    public function __construct(UuidGeneratorInterface $uuidGenerator, IriGeneratorInterface $iriGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
        $this->iriGenerator = $iriGenerator;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $data->{'@id'} = $this->iriGenerator->iri(
            $this->uuidGenerator->generate()
        );

        $data->url = $data->website;

        $data->name = [
            $data->mainLanguage => $data->name
        ];

        if (isset($data->address)) {
            $data->address = [
                $data->mainLanguage => $data->address
            ];
        }

        if (isset($data->contact)) {
            foreach ($data->contact as $contactEntry) {
                switch ($contactEntry->type) {
                    case 'phone':
                        $phones[] = $contactEntry->value;
                        break;

                    case 'email':
                        $emails[] = $contactEntry->value;
                        break;

                    case 'url':
                        $urls[] = $contactEntry->value;
                        break;
                }
            }

            $data->contactPoint = [
                'phone' => $phones ?? [],
                'email' => $emails ?? [],
                'url' => $urls ?? [],
            ];
        }

        return $request->withParsedBody($data);
    }
}
