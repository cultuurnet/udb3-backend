<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Zipped;

use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveFileWriter;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

/**
 * Creates a ZIP archive of a an HTML export and all needed assets.
 *
 * Inside the zip file, all files are located in a 'html' folder.
 */
class ZippedWebArchiveFileWriter extends WebArchiveFileWriter
{
    /**
     * {@inheritdoc}
     */
    public function write($filePath, $events)
    {
        $directory = $this->createWebArchiveDirectory($events);

        $this->zipDirectory($directory, $filePath);

        $this->removeTemporaryArchiveDirectory($directory);
    }

    protected function zipDirectory($directory, $filePath)
    {
        $zipArchive = new ZipArchiveAdapter(
            $filePath
        );

        $this->mountManager->mountFilesystem(
            'zip',
            new Filesystem(
                $zipArchive
            )
        );

        $webArchiveFiles = $this->mountManager->listContents(
            'tmp://' . $directory,
            true
        );

        foreach ($webArchiveFiles as $file) {
            if ($file['type'] !== 'file') {
                continue;
            }

            $from = $file['filesystem'] . '://' . $file['path'];

            $pathWithoutUpperDirectory = $this->pathWithoutUpperDirectory(
                $file['path']
            );
            $to = 'zip://html/' . $pathWithoutUpperDirectory;


            $this->mountManager->copy($from, $to);
        }

        // Need to close the archive in order to actually save it.
        $zipArchive->getArchive()->close();
    }

    protected function pathWithoutUpperDirectory($file)
    {
        $pos = strpos($file, '/');

        if (false !== $pos) {
            return substr($file, $pos);
        } else {
            return $file;
        }
    }
}
