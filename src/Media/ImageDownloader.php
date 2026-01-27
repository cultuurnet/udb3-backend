<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Psr\Http\Message\UploadedFileInterface;

interface ImageDownloader
{
    public function download(Url $url): UploadedFileInterface;
}
