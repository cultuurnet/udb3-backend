#!/usr/bin/env bash

# Runs the default acceptance test suite, with the offer and search features in parallel.
# The features that change shared state run first and in sequence, as the shared-state suite in behat.php.

set -u

# The same directories as $runLast in behat.php
parallelGroups=(event place organizer search)
logDirectory=output/parallel

rm -rf "$logDirectory"
mkdir -p "$logDirectory"
SECONDS=0

echo "Running the shared state features in sequence"
vendor/bin/behat --suite=shared-state
exitCode=$?

echo "Running ${parallelGroups[*]} in parallel, logs in $logDirectory"
for group in "${parallelGroups[@]}"; do
    # Clearing the shared token cache at the start of every run would pull tokens away from the others
    KEEP_TOKEN_CACHE=true vendor/bin/behat --suite=default "features/$group" > "$logDirectory/$group.log" 2>&1 &
done

for job in $(jobs -p); do
    wait "$job" || exitCode=1
done

for group in "${parallelGroups[@]}"; do
    echo
    echo "== $group"
    sed -n '/^--- Failed scenarios:/,/^$/p' "$logDirectory/$group.log"
    tail -n 3 "$logDirectory/$group.log"
done

echo
echo "Total time: $((SECONDS / 60))m$((SECONDS % 60))s"
exit $exitCode
