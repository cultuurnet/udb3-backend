<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('default'))
                    ->withPaths('%paths.base%/features')
                    ->withContexts('FeatureContext')
                    ->withFilter(new TagFilter('~@init&&~@external'))
            )
            ->withSuite(
                (new Suite('sapi3'))
                    ->withPaths('%paths.base%/features')
                    ->withContexts('FeatureContext')
                    ->withFilter(new TagFilter('@sapi3'))
            )
            ->withSuite(
                (new Suite('init'))
                    ->withPaths('%paths.base%/features')
                    ->withContexts('FeatureContext')
                    ->withFilter(new TagFilter('@init'))
            )
    );
