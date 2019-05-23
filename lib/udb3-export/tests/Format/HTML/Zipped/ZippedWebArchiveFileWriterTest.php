<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Zipped;

use Alchemy\Zippy\Zippy;
use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;
use CultuurNet\UDB3\EventExport\Format\HTML\Zipped\ZippedWebArchiveFileWriter;

use \Twig_Environment;
use \Twig_Loader_Filesystem;

class ZippedWebArchiveFileWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->filePath = $this->getFilePath();
    }

    /**
     * @return string
     */
    protected function getFilePath()
    {
        return tempnam(sys_get_temp_dir(), uniqid()) . '.zip';
    }

    /**
     * @test
     */
    public function it_zips_a_html_file_and_its_assets()
    {
        $this->assertFileNotExists($this->filePath);

        $writer = new ZippedWebArchiveFileWriter(
            new HTMLFileWriter(
                'hello.html.twig',
                [
                    'name' => 'world',
                ],
                new Twig_Environment(
                    new Twig_Loader_Filesystem(__DIR__ . '/../templates')
                )
            )
        );

        $events = [];
        $writer->write($this->filePath, $events);

        $this->assertFileExists($this->filePath);

        $zippy = Zippy::load();

        $archive = $zippy->open($this->filePath);

        $extractToDir = sys_get_temp_dir() . '/' . uniqid('html-extract');

        $this->assertFileNotExists($extractToDir);
        mkdir($extractToDir);

        $archive->extract($extractToDir);

        $this->assertFileExists($extractToDir . '/html/index.html');
        $this->assertFileEquals(
            __DIR__ . '/../results/hello-world.html',
            $extractToDir . '/html/index.html'
        );

        $this->assertFileExists($extractToDir . '/html/fonts');
        $this->assertFileExists($extractToDir . '/html/css');
        $this->assertFileExists($extractToDir . '/html/img');
    }
}
