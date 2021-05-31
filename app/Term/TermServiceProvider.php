<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Term;

use CultuurNet\UDB3\Term\TermRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

final class TermServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app[TermRepository::class] = $app::share(
            function () {
                $mapping = [];

                $files = [
                    __DIR__ . '/../../term_mapping_facilities.yml',
                    __DIR__ . '/../../term_mapping_themes.yml',
                    __DIR__ . '/../../term_mapping_types.yml',
                ];

                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $yaml = file_get_contents($file);
                        $yaml = Yaml::parse($yaml);

                        if (is_array($yaml)) {
                            $mapping = array_merge($mapping, $yaml);
                        }
                    }
                }

                return new TermRepository($mapping);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
