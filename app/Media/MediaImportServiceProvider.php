<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Model\Import\MediaObject\MediaManagerImageCollectionFactory;

final class MediaImportServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return ['import_image_collection_factory'];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'import_image_collection_factory',
            function () use ($container) {
                return new MediaManagerImageCollectionFactory(
                    $container->get('media_manager')
                );
            }
        );
    }
}
