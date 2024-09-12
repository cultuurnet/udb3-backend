<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection;

use CultuurNet\UDB3\Collection\Exception\CollectionItemNotFoundException;
use CultuurNet\UDB3\Collection\Exception\CollectionKeyNotFoundException;
use CultuurNet\UDB3\Collection\Mock\Foo;
use CultuurNet\UDB3\Collection\Mock\FooCollection;
use CultuurNet\UDB3\Collection\Mock\FooExtended;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    protected Foo $foo1;

    protected Foo $foo2;

    protected FooExtended $fooExtended;

    protected \stdClass $notFoo;

    protected function setUp(): void
    {
        $this->foo1 = new Foo(1, 'Foo 1');
        $this->foo2 = new Foo(2, 'Foo 2');

        $this->fooExtended = new FooExtended(3, 'Foo 3 (extended)');

        $this->notFoo = new \stdClass();
    }

    /**
     * @test
     */
    public function it_can_create_a_copy_with_a_new_item_for_a_specific_key(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $expected = [
            'foo1' => $this->foo1,
            'foo2' => $this->foo2,
        ];

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_can_assign_a_key_automatically_and_return_it_when_necessary(): void
    {
        $collection = (new FooCollection())
            ->with($this->foo1)
            ->with($this->foo2);

        $expectedArray = [
            0 => $this->foo1,
            1 => $this->foo2,
        ];

        $this->assertEquals($expectedArray, $collection->toArray());

        $this->assertEquals(0, $collection->getKeyFor($this->foo1));
        $this->assertEquals(1, $collection->getKeyFor($this->foo2));
    }

    /**
     * @test
     */
    public function it_guards_the_object_type_of_new_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FooCollection())
            ->with($this->notFoo);
    }

    /**
     * @test
     */
    public function it_guards_the_object_type_of_items_when_initializing_from_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FooCollection::fromArray(
            [
                $this->foo1,
                $this->notFoo,
                $this->foo2,
            ]
        );
    }

    /**
     * @test
     */
    public function it_allows_sub_classes_when_guarding_the_object_type_of_new_items(): void
    {
        $collection = (new FooCollection())
            ->withKey('fooExtended', $this->fooExtended);

        $expected = [
            'fooExtended' => $this->fooExtended,
        ];

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_can_remove_an_item(): void
    {
        $collection = (new FooCollection())
            ->with($this->foo1)
            ->with($this->foo2);

        $collection = $collection->without($this->foo1);

        $expected = [
            1 => $this->foo2,
        ];

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_removing_an_unknown_item(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1);

        $this->expectException(CollectionItemNotFoundException::class);
        $collection->without($this->foo2);
    }

    /**
     * @test
     */
    public function it_can_remove_an_item_by_key(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $collection = $collection->withoutKey('foo1');

        $expected = [
            'foo2' => $this->foo2,
        ];

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_removing_by_unknown_key(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $this->expectException(CollectionKeyNotFoundException::class);
        $collection->withoutKey('foo3');
    }

    /**
     * @test
     */
    public function it_can_check_if_the_collection_contains_a_given_item(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1);

        $this->assertTrue($collection->contains($this->foo1));
    }

    /**
     * @test
     */
    public function it_can_check_if_the_collection_does_not_contain_a_given_item(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1);

        $this->assertFalse($collection->contains($this->foo2));
    }

    /**
     * @test
     */
    public function it_can_return_an_item_by_key(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $this->assertEquals(
            $this->foo1,
            $collection->getByKey('foo1')
        );

        $this->assertEquals(
            $this->foo2,
            $collection->getByKey('foo2')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_looking_for_an_item_with_an_unknown_key(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1);

        $this->expectException(CollectionKeyNotFoundException::class);
        $collection->getByKey('foo2');
    }

    /**
     * @test
     */
    public function it_can_return_the_key_for_an_item(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $this->assertEquals(
            'foo1',
            $collection->getKeyFor($this->foo1)
        );

        $this->assertEquals(
            'foo2',
            $collection->getKeyFor($this->foo2)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_looking_for_the_key_of_an_unknown_item(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1);

        $this->expectException(CollectionItemNotFoundException::class);
        $collection->getKeyFor($this->foo2);
    }

    /**
     * @test
     */
    public function it_can_return_a_list_of_keys(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2);

        $expected = [
            'foo1',
            'foo2',
        ];

        $this->assertEquals($expected, $collection->getKeys());
    }

    /**
     * @test
     */
    public function it_can_return_the_number_of_items(): void
    {
        $collection = new FooCollection();
        $this->assertEquals(0, $collection->length());

        /* @var FooCollection $collection */
        $collection = $collection->with($this->foo1);
        $collection = $collection->with($this->foo2);

        $this->assertEquals(2, $collection->length());
    }

    /**
     * @test
     */
    public function it_can_be_converted_from_and_to_an_array(): void
    {
        $original = [
            $this->foo1,
            $this->foo2,
        ];

        $collection = FooCollection::fromArray($original);

        $this->assertEquals($original, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_can_be_looped_over_like_an_array(): void
    {
        $collection = (new FooCollection())
            ->withKey('foo1', $this->foo1)
            ->withKey('foo2', $this->foo2)
            ->withKey('fooExtended', $this->fooExtended);

        $expected = [
            'foo1' => $this->foo1,
            'foo2' => $this->foo2,
            'fooExtended' => $this->fooExtended,
        ];

        $actual = [];

        foreach ($collection as $key => $item) {
            $actual[$key] = $item;
        }

        $this->assertEquals($expected, $actual);
    }
}
