<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Term;

use CultuurNet\UDB3\Term\TermRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class TermServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[TermRepository::class] = $app::share(
            function () {
                $mapping = [];

                $files = [
                    __DIR__ . '/../../../config.term_mapping_facilities.php',
                    __DIR__ . '/../../../config.term_mapping_themes.php',
                    __DIR__ . '/../../../config.term_mapping_types.php',
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
        );
    }

    public function boot(Application $app): void
    {
    }
}
