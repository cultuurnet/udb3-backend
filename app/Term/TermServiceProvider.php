<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class TermServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            TermRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            TermRepository::class,
            fn () => $this->provideTermRepository()
        );
    }

    private function provideTermRepository(): TermRepository
    {
        $mapping = [];

        $files = [
            __DIR__ . '/../../term_mapping_facilities.php',
            __DIR__ . '/../../term_mapping_themes.php',
            __DIR__ . '/../../term_mapping_types.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $terms  = require $file;

                if (is_array($terms)) {
                    $mapping = array_merge($mapping, $terms);
                }
            }
        }

        return new TermRepository($mapping);
    }
}
