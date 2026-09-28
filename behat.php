<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;

// Features that change shared state (roles, permissions, labels and ownerships of the test users) run first,
// and the offer and search features last, so those last ones can later run in parallel.
$runFirst = ['role', 'permission', 'label', 'ownership'];
$runLast = ['event', 'place', 'organizer', 'search'];
$allDirectories = array_map(
    'basename',
    array_filter(
        glob(__DIR__ . '/features/*', GLOB_ONLYDIR),
        fn (string $directory): bool => glob($directory . '/*.feature') !== []
    )
);
$runInBetween = array_diff($allDirectories, $runFirst, $runLast);

$toPaths = fn (array $directories): array => array_map(
    fn (string $directory): string => '%paths.base%/features/' . $directory,
    $directories
);
$orderedPaths = $toPaths([...$runFirst, ...$runInBetween, ...$runLast]);
$sharedStatePaths = $toPaths([...$runFirst, ...$runInBetween]);

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('default'))
                    ->withPaths(...$orderedPaths)
                    ->withContexts('FeatureContext')
                    ->withFilter(new TagFilter('~@init&&~@external'))
            )
            // The first part of the default suite, which a parallel run runs in sequence before the rest,
            // see bin/feature-parallel.sh
            ->withSuite(
                (new Suite('shared-state'))
                    ->withPaths(...$sharedStatePaths)
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
