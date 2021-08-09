<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Slim\Psr7\Interfaces\HeadersInterface;

class PagedCollectionResponse extends JsonLdResponse
{
    public function __construct(
        int $itemsPerPage,
        int $totalItems,
        array $members = [],
        $status = 200,
        ?HeadersInterface $headers = null
    ) {
        $data = [
            '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
            '@type' => 'PagedCollection',
            'itemsPerPage' => $itemsPerPage,
            'totalItems' => $totalItems,
            'member' => $members,
        ];
        parent::__construct($data, $status, $headers);
    }
}
