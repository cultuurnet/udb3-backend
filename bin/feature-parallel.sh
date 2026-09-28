#!/usr/bin/env bash

# Runs the default acceptance test suite, with the offer and search features in parallel.
# The features that change shared state run first and in sequence, as the shared-state suite in behat.php.
# With JUNIT_DIRECTORY set, every run also writes a JUnit file there, as <run>.xml.

set -u

# The same directories as $runLast in behat.php
parallelGroups=(event place organizer search)
logDirectory=output/parallel
junitDirectory=${JUNIT_DIRECTORY:-}

rm -rf "$logDirectory"
mkdir -p "$logDirectory"
SECONDS=0

# Behat names a JUnit file after its suite and does not create nested directories,
# so every run writes into its own directory and the file is moved next to the others afterwards
junitOptions() {
    if [ -n "$junitDirectory" ]; then
        mkdir -p "$junitDirectory/$1"
        echo "-f pretty -o std -f junit -o $junitDirectory/$1"
    fi
}

moveJunitFile() {
    if [ -n "$junitDirectory" ]; then
        mv "$junitDirectory/$1"/*.xml "$junitDirectory/$1.xml"
        rmdir "$junitDirectory/$1"
    fi
}

echo "Running the shared state features in sequence"
# shellcheck disable=SC2046
vendor/bin/behat --suite=shared-state $(junitOptions shared-state)
exitCode=$?
moveJunitFile shared-state

echo "Running ${parallelGroups[*]} in parallel, logs in $logDirectory"
for group in "${parallelGroups[@]}"; do
    # Clearing the shared token cache at the start of every run would pull tokens away from the others
    # shellcheck disable=SC2046
    KEEP_TOKEN_CACHE=true vendor/bin/behat --suite=default "features/$group" $(junitOptions "$group") \
        > "$logDirectory/$group.log" 2>&1 &
done

for job in $(jobs -p); do
    wait "$job" || exitCode=1
done

for group in "${parallelGroups[@]}"; do
    moveJunitFile "$group"

    echo
    echo "== $group"
    sed -n '/^--- Failed scenarios:/,/^$/p' "$logDirectory/$group.log"
    tail -n 3 "$logDirectory/$group.log"
done

echo
echo "Total time: $((SECONDS / 60))m$((SECONDS % 60))s"
exit $exitCode
