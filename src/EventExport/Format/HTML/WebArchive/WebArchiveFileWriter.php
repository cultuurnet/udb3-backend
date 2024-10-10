<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\HTMLEventFormatter;
use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\FileWriterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use Traversable;

abstract class WebArchiveFileWriter implements FileWriterInterface
{
    protected HTMLFileWriter $htmlFileWriter;

    protected MountManager $mountManager;

    protected string $tmpDir;

    protected ?EventInfoServiceInterface $uitpas;

    protected ?CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    public function __construct(
        HTMLFileWriter $htmlFileWriter,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->htmlFileWriter = $htmlFileWriter;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;

        $this->tmpDir = sys_get_temp_dir();

        $this->mountManager = $this->initMountManager($this->tmpDir);
    }

    /**
     *   The path of the temporary directory, relative to the 'tmp://' mounted
     *   filesystem.
     */
    protected function createWebArchiveDirectory(Traversable $events): string
    {
        $tmpDir = $this->createTemporaryArchiveDirectory();

        $this->writeHtml($tmpDir, $events);
        $this->copyAssets($tmpDir);

        return $tmpDir;
    }

    protected function copyAssets(string $tmpDir): void
    {
        $assets = $this->mountManager->listContents('assets:///', true);

        foreach ($assets as $asset) {
            if ($asset['type'] !== 'file') {
                continue;
            }

            // The path property now also includes the mount prefix
            $this->mountManager->copy(
                $asset['path'],
                'tmp://' . $tmpDir . '/' . str_replace('assets://', '', $asset['path'])
            );
        };
    }

    protected function initMountManager(string $tmpDir): MountManager
    {
        return new MountManager(
            [
                'tmp' => new Filesystem(
                    new LocalFilesystemAdapter($tmpDir)
                ),
                // @todo make this configurable
                'assets' => new Filesystem(
                    new LocalFilesystemAdapter(__DIR__ . '/assets')
                ),
            ]
        );
    }

    protected function removeTemporaryArchiveDirectory(string $tmpDir): void
    {
        $this->mountManager->deleteDirectory('tmp://' . $tmpDir);
    }

    /**
     *   The path of the temporary directory, relative to the 'tmp://' mounted
     *   filesystem.
     */
    protected function createTemporaryArchiveDirectory(): string
    {
        $exportDir = uniqid('html-export');
        $path = 'tmp://' . $exportDir;
        $this->mountManager->createDirectory($path);

        return $exportDir;
    }

    /**
     * Expands a path relative to the tmp:// mount point to a full path.
     */
    protected function expandTmpPath(string $tmpPath): string
    {
        return $this->tmpDir . '/' . $tmpPath;
    }

    /**
     * @param \Traversable|array $events
     */
    protected function writeHtml(string $dir, $events): void
    {
        $filePath = $dir . '/index.html';

        // TransformingIteratorIterator requires a Traversable,
        // so if $events is a regular array we need to wrap it
        // inside an ArrayIterator.
        if (!is_array($events)) {
            return;
        }

        $formatter = new HTMLEventFormatter($this->uitpas, $this->calendarSummaryRepository);

        $formattedEvents = [];

        foreach ($events as $eventLocation => $event) {
           $formattedEvents[]= $this->transform($event, $eventLocation, $formatter);
        }

        $this->htmlFileWriter->write(
            $this->expandTmpPath($filePath),
            new \ArrayIterator($formattedEvents)
        );
    }

    private function transform($event, string $eventLocation, HTMLEventFormatter $formatter): array
    {
        $logFile = fopen('/var/www/html/debugLog.txt', 'w');
        fwrite($logFile, gettype($event));
        fclose($logFile);
        $urlParts = explode('/', $eventLocation);
        $eventId = array_pop($urlParts);
        return $formatter->formatEvent($eventId, $event);
    }

    abstract public function write(string $filePath, \Traversable $events): void;
}
