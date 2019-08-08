<?php

namespace CultuurNet\UDB3\Symfony\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

class EditMediaRestController
{
    /**
     * @var ImageUploaderInterface
     */
    private $imageUploader;

    public function __construct(ImageUploaderInterface $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }

    public function upload(Request $request)
    {
        if (!$request->files->has('file')) {
            return new JsonResponse(['error' => "file required"], 400);
        }

        $description = $request->request->get('description');
        $copyrightHolder = $request->request->get('copyrightHolder');
        $language = $request->request->get('language');

        if (!$description) {
            return new JsonResponse(['error' => "description required"], 400);
        }

        if (!$copyrightHolder) {
            return new JsonResponse(['error' => "copyright holder required"], 400);
        }

        if (!$language) {
            return new JsonResponse(['error' => "language required"], 400);
        }

        $file = $request->files->get('file');

        $imageId = $this->imageUploader->upload(
            $file,
            new StringLiteral($description),
            new StringLiteral($copyrightHolder),
            new Language($language)
        );

        return new JsonResponse(
            [
                'imageId' => $imageId->toNative(),
            ],
            201
        );
    }
}
