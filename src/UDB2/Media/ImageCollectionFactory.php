<?php

namespace CultuurNet\UDB3\UDB2\Media;

use CultureFeed_Cdb_Data_File;
use CultureFeed_Cdb_Data_Media;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use League\Uri\Modifiers\AbstractUriModifier;
use League\Uri\Modifiers\Normalize;
use League\Uri\Schemes\Http;
use Psr\Http\Message\UriInterface;
use ValueObjects\Identity\UUID;
use Rhumsaa\Uuid\Uuid as BaseUuid;
use ValueObjects\Web\Url;

class ImageCollectionFactory implements ImageCollectionFactoryInterface
{
    const SUPPORTED_UDB2_MEDIA_TYPES = [
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO,
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_IMAGEWEB,
    ];

    /**
     * @var AbstractUriModifier
     */
    protected $uriNormalizer;

    /**
     * @var string|null
     */
    protected $uuidRegex;

    public function __construct()
    {
        $this->uriNormalizer = new Normalize();
    }

    public function withUuidRegex($mediaIdentifierRegex)
    {
        $c = clone $this;
        $c->uuidRegex = $mediaIdentifierRegex;

        return $c;
    }

    /**
     * Create an ImageCollection from the media in the Dutch details of on an UDB2 item.
     *
     * @param CultureFeed_Cdb_Item_Base $item
     * @return ImageCollection
     */
    public function fromUdb2Item(CultureFeed_Cdb_Item_Base $item)
    {
        $details = $item->getDetails();
        $detail = $details->getDetailByLanguage('nl');

        if (!$detail) {
            $details->rewind();
            $detail = $details->current();
        }

        if (!$detail) {
            return new ImageCollection();
        }

        $title = $detail->getTitle();

        return $this->fromUdb2Media(
            $detail->getMedia(),
            new Description($title),
            new CopyrightHolder($title),
            new Language($detail->getLanguage())
        );
    }

    /**
     * @param CultureFeed_Cdb_Data_Media $media
     * @param Description $fallbackDescription ,
     * @param CopyrightHolder $fallbackCopyright
     * @return ImageCollection
     */
    private function fromUdb2Media(
        \CultureFeed_Cdb_Data_Media $media,
        Description $fallbackDescription,
        CopyrightHolder $fallbackCopyright,
        Language $language
    ) {
        $udb2ImageFiles = $media->byMediaTypes(self::SUPPORTED_UDB2_MEDIA_TYPES);

        return array_reduce(
            iterator_to_array($udb2ImageFiles),
            function (
                ImageCollection $images,
                CultureFeed_Cdb_Data_File $file
            ) use (
                $fallbackDescription,
                $fallbackCopyright,
                $language
            ) {
                $udb2Description = $file->getDescription();
                $udb2Copyright = $file->getCopyright();
                $normalizedUri = $this->normalize($file->getHLink());
                $fileType = $file->getFileType();
                $image = new Image(
                    $this->identify($normalizedUri),
                    empty($fileType) ? MIMEType::fromSubtype('octet-stream') : MIMEType::fromSubtype($fileType),
                    empty($udb2Description) ? $fallbackDescription : new Description($udb2Description),
                    empty($udb2Copyright) ? $fallbackCopyright : new CopyrightHolder($udb2Copyright),
                    Url::fromNative((string) $normalizedUri),
                    $language
                );

                return !$images->getMain() && $file->isMain()
                    ? $images->withMain($image)
                    : $images->with($image);
            },
            new ImageCollection()
        );
    }

    /**
     * @param string $link
     * @return UriInterface
     */
    private function normalize($link)
    {
        $originalUri = Http::createFromString($link)->withScheme('http');
        return $this->uriNormalizer->__invoke($originalUri);
    }

    /**
     * @param UriInterface $httpUri
     * @return UUID
     */
    private function identify(Http $httpUri)
    {
        if (isset($this->uuidRegex) && \preg_match('/' . $this->uuidRegex . '/', (string) $httpUri, $matches)) {
            return UUID::fromNative($matches['uuid']);
        }

        $namespace = BaseUuid::uuid5(BaseUuid::NAMESPACE_DNS, $httpUri->getHost());
        return UUID::fromNative((string) BaseUuid::uuid5($namespace, (string) $httpUri));
    }
}
