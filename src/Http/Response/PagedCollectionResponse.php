<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class PagedCollectionResponse extends JsonResponse
{
    public function __construct(
        int $itemsPerPage,
        int $totalItems,
        array $members = [],
        $status = 200,
        $headers = []
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
