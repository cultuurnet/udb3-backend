<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\MissingContentTypeException;
use CultuurNet\UDB3\Role\UnknownContentTypeException;
use Symfony\Component\HttpFoundation\Request;

class UpdateRoleRequestDeserializer
{
    public function deserialize(Request $request, $roleId)
    {
        $contentType = $request->headers->get('Content-Type');
        $body_content = json_decode($request->getContent());

        if (empty($contentType)) {
            throw new MissingContentTypeException();
        }

        switch ($contentType) {
            case 'application/ld+json;domain-model=RenameRole':
                return new RenameRole(
                    new UUID($roleId),
                    $body_content->name
                );

            default:
                throw new UnknownContentTypeException();
        }
    }
}
