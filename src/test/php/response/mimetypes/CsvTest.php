<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\response\Error;
/**
 * Helper class for the test.
 */
class ToArray
{
    /**
     * @return  array
     */
    public function toArray()
    {
        return ['column1' => 'foo', 'column2' => 'bar'];
    }
}
/**
 * Helper class for the test.
 */
class AsArray
{
    /**
     * @return  array
     */
    public function asArray()
    {
        return ['column1' => 'foo', 'column2' => 'bar'];
    }
}
/**
 * Tests for stubbles\webapp\response\mimetypes\Csv.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class CsvTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Csv
     */
    private $csv;
    /**
     * @type  \stubbles\streams\memory\MemoryOutputStream
     */
    private $memory;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->csv    = new Csv();
        $this->memory = new MemoryOutputStream();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        $this->assertEquals(
                'text/csv',
                (string) $this->csv
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        $this->assertEquals(
                'text/vendor-csv',
                (string) $this->csv->specialise('text/vendor-csv')
        );
    }

    /**
     * @return  array
     */
    public function scalarValues()
    {
        return [[1, '1'], ['some text', 'some text'], [true, '1'], [false, '']];
    }

    /**
     * @param  scalar  $scalarValue
     * @test
     * @dataProvider  scalarValues
     */
    public function scalarResourcesAreConvertedToOneLineCsv($scalarValue, $expected)
    {
        $this->assertEquals(
                $expected . "\n",
                $this->csv->serialize($scalarValue, $this->memory)->buffer()
        );
    }

    /**
     * @test
     */
    public function errorResourceIsConvertedToOneLineCsv()
    {
        $this->assertEquals(
                "Error: ups\n",
                $this->csv->serialize(new Error('ups'), $this->memory)->buffer()
        );
    }

    /**
     * @test
     * @expectedException  PHPUnit_Framework_Error
     * @expectedExceptionMessage  Resource of type resource[stream] can not be serialized to csv
     */
    public function incompatibleResourceTriggersError()
    {
        $this->assertEquals(
                '',
                $this->csv->serialize(fopen(__FILE__, 'r'), $this->memory)
                        ->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeSimpleObjectExtractsProperties()
    {
        $object = new \stdClass();
        $object->column1 = 'bar';
        $object->column2 = 'baz';
        $this->assertEquals(
                "column1,column2\nbar,baz\n",
                $this->csv->serialize($object, $this->memory)->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeSimpleListWritesOneLine()
    {
        $this->assertEquals(
                "bar,baz\n",
                $this->csv->serialize(
                        ['bar', 'baz'],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     *
     * This behaviour is different then the one for array because for a
     * \Traversable we can not inspect the first element to check if it is
     * something iterable or just a scalar value as we can with arrays.
     */
    public function serializeSimpleTravsersableListWritesOneLineForEachEntry()
    {
        $this->assertEquals(
                "bar\nbaz\n",
                $this->csv->serialize(
                        new \ArrayIterator(['bar', 'baz']),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeSimpleMapWritesHeaderLineAndOneValueLine()
    {
        $this->assertEquals(
                "column1,column2\nbar,baz\n",
                $this->csv->serialize(
                        ['column1' => 'bar', 'column2' => 'baz'],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeNestedArray()
    {
        $this->assertEquals(
                "bar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        [['bar', 'baz'], ['foo', 'dummy']],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeNestedAssociativeArray()
    {
        $this->assertEquals(
                "column1,column2\nbar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        [['column1' => 'bar', 'column2' => 'baz'],
                         ['column1' => 'foo', 'column2' => 'dummy']
                        ],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeListOfObjects()
    {
        $object1 = new \stdClass();
        $object1->column1 = 'bar';
        $object1->column2 = 'baz';
        $object2 = new \stdClass();
        $object2->column1 = 'foo';
        $object2->column2 = 'dummy';
        $this->assertEquals(
                "column1,column2\nbar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        [$object1, $object2],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeTraversableOfObjects()
    {
        $object1 = new \stdClass();
        $object1->column1 = 'bar';
        $object1->column2 = 'baz';
        $object2 = new \stdClass();
        $object2->column1 = 'foo';
        $object2->column2 = 'dummy';
        $this->assertEquals(
                "column1,column2\nbar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        new \ArrayIterator([$object1, $object2]),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeTraversable()
    {
        $this->assertEquals(
                "column1,column2\nbar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        new \ArrayIterator(
                                [['column1' => 'bar', 'column2' => 'baz'],
                                 ['column1' => 'foo', 'column2' => 'dummy']
                                ]
                        ),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeNonAssociativeTraversable()
    {
        $this->assertEquals(
                "bar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        new \ArrayIterator([['bar', 'baz'], ['foo', 'dummy']]),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeNestedTraversable()
    {
        $this->assertEquals(
                "column1,column2\nbar,baz\nfoo,dummy\n",
                $this->csv->serialize(
                        new \ArrayIterator(
                                [new \ArrayIterator(['column1' => 'bar', 'column2' => 'baz']),
                                 new \ArrayIterator(['column1' => 'foo', 'column2' => 'dummy'])
                                ]
                        ),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function supportsToArrayOnObjects()
    {
        $this->assertEquals(
                "column1,column2\nfoo,bar\n",
                $this->csv->serialize(
                        new ToArray(),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function supportsNestedToArrayOnObjects()
    {
        $this->assertEquals(
                "column1,column2\nfoo,bar\nfoo,bar\n",
                $this->csv->serialize(
                        [new ToArray(), new ToArray()],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function supportsAsArrayOnObjects()
    {
        $this->assertEquals(
                "column1,column2\nfoo,bar\n",
                $this->csv->serialize(
                        new AsArray(),
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function supportsNestedAsArrayOnObjects()
    {
        $this->assertEquals(
                "column1,column2\nfoo,bar\nfoo,bar\n",
                $this->csv->serialize(
                        [new AsArray(), new AsArray()],
                        $this->memory
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeWithChangedDelimiter()
    {
        $this->assertEquals(
                "bar;baz\n",
                $this->csv->changeDelimiterTo(';')
                        ->serialize(['bar', 'baz'], $this->memory)
                        ->buffer()
        );
    }

    /**
     * @test
     */
    public function serializeWithChangedEnclosure()
    {
        $this->assertEquals(
                "bar,/b//az/\n",
                $this->csv->changeEnclosureTo('/')
                        ->serialize(['bar', 'b/az'], $this->memory)
                        ->buffer()
        );
    }
}
