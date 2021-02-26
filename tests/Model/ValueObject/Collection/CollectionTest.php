<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_accept_a_list_of_values_and_return_an_array_later()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);

        $this->assertEquals($values, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_should_only_accept_objects_as_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for key 0 is not an object.');

        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        new BrokenMockCollection(...$values);
    }

    /**
     * @test
     */
    public function it_should_accept_an_array_of_values_and_return_the_same_array_later()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = MockCollection::fromArray($values);

        $this->assertEquals($values, $collection->toArray());
    }

    /**
     * @test
     */
    public function it_should_be_iterable()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);

        $actual = [];
        foreach ($collection as $value) {
            $actual[] = $value;
        }

        $this->assertEquals($values, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_countable()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);

        $expected = count($values);
        $actual = count($collection);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_able_to_check_if_the_collection_is_empty_or_not()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);
        $emptyCollection = new MockCollection();

        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($emptyCollection->isEmpty());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_tell_if_a_given_value_exists_within_the_collection()
    {
        $values = [
            new MockString('foo'),
            new MockString('bar'),
        ];

        $collection = new MockCollection(...$values);

        $this->assertTrue($collection->contains(new MockString('foo')));
        $this->assertTrue($collection->contains(new MockString('bar')));

        $this->assertFalse($collection->contains('foo'));
        $this->assertFalse($collection->contains('bar'));

        $this->assertFalse($collection->contains(new MockString('lorem')));
        $this->assertFalse($collection->contains(new MockString('ipsum')));
    }

    /**
     * @test
     */
    public function it_should_filter_with_a_given_callback()
    {
        $values = [
            new MockString('foo'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
        ];

        $expected = [
            new MockString('foo'),
            new MockString('bar'),
        ];

        $callback = function (MockString $string) {
            return !(strlen($string->toString()) > 3);
        };

        $collection = new MockCollection(...$values);
        $filtered = $collection->filter($callback);

        $this->assertEquals($expected, $filtered->toArray());
    }

    /**
     * @test
     */
    public function it_should_return_a_value_by_index()
    {
        $values = [
            new MockString('foo'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);

        $this->assertEquals($values[0], $collection->getByIndex(0));
        $this->assertEquals($values[1], $collection->getByIndex(1));
        $this->assertEquals($values[2], $collection->getByIndex(2));
        $this->assertEquals($values[3], $collection->getByIndex(3));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_value_exists_at_the_given_index()
    {
        $values = [
            new MockString('foo'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);

        $this->expectException(\OutOfBoundsException::class);

        $collection->getByIndex(4);
    }

    /**
     * @test
     */
    public function it_should_return_the_first_value_or_null_if_the_collection_is_empty()
    {
        $values = [
            new MockString('foo'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);
        $emptyCollection = new MockCollection();

        $this->assertEquals($values[0], $collection->getFirst());
        $this->assertNull($emptyCollection->getFirst());
    }

    /**
     * @test
     */
    public function it_should_return_the_last_value_or_null_if_the_collection_is_empty()
    {
        $values = [
            new MockString('foo'),
            new MockString('lorem'),
            new MockString('bar'),
            new MockString('ipsum'),
        ];

        $collection = new MockCollection(...$values);
        $emptyCollection = new MockCollection();

        $this->assertEquals($values[3], $collection->getLast());
        $this->assertNull($emptyCollection->getLast());
    }

    /**
     * @test
     */
    public function it_should_append_each_new_value_to_a_copy()
    {
        $initial = new MockCollection(new MockString('foo'));
        $second = $initial->with(new MockString('bar'));
        $third = $second->with(new MockString('lorem'));

        $expectedInitial = [
            new MockString('foo'),
        ];

        $expectedSecond = [
            new MockString('foo'),
            new MockString('bar'),
        ];

        $expectedThird = [
            new MockString('foo'),
            new MockString('bar'),
            new MockString('lorem'),
        ];

        $this->assertEquals($expectedInitial, $initial->toArray());
        $this->assertEquals($expectedSecond, $second->toArray());
        $this->assertEquals($expectedThird, $third->toArray());
    }
}
