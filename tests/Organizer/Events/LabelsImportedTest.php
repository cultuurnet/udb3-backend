<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class LabelsImportedTest extends TestCase
{
    private LabelsImported $labelsImported;

    private array $labelsImportedAsArray;

    protected function setUp(): void
    {
        $this->labelsImported = new LabelsImported(
            '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            ['foo'],
            ['bar']
        );

        $this->labelsImportedAsArray = [
            'organizer_id' => '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            'labels' => [
                [
                    'label' => 'foo',
                    'visibility' => true,
                ],
                [
                    'label' => 'bar',
                    'visibility' => false,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            '0e9fcb97-dd06-45e1-b32e-ff18967f3836',
            $this->labelsImported->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_all_labels(): void
    {
        $this->assertEquals(
            [
                 'foo',
                 'bar',
             ],
            $this->labelsImported->getAllLabelNames()
        );
    }

    /**
     * @test
     */
    public function it_stores_visible_labels(): void
    {
        $this->assertEquals(
            [
                'foo',
            ],
            $this->labelsImported->getVisibleLabelNames()
        );
    }

    /**
     * @test
     */
    public function it_stores_hidden_labels(): void
    {
        $this->assertEquals(
            [
                'bar',
            ],
            $this->labelsImported->getHiddenLabelNames()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->labelsImported,
            LabelsImported::deserialize(
                $this->labelsImportedAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            $this->labelsImportedAsArray,
            $this->labelsImported->serialize()
        );
    }
}
