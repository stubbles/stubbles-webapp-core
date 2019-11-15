<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use PHPUnit\Framework\TestCase;
use stubbles\helper\response\mimetypes\{ToArray, AsArray};
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\response\Error;

use function bovigo\assert\assertThat;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isEmpty;
/**
 * Tests for stubbles\webapp\response\mimetypes\Csv.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class CsvTest extends TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Csv
     */
    private $csv;
    /**
     * @type  \stubbles\streams\memory\MemoryOutputStream
     */
    private $memory;

    protected function setUp(): void
    {
        $this->csv    = new Csv();
        $this->memory = new MemoryOutputStream();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assertThat((string) $this->csv, equals('text/csv'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assertThat(
                (string) $this->csv->specialise('text/vendor-csv'),
                equals('text/vendor-csv')
        );
    }

    public function scalarValues(): array
    {
        return [[1, '1'], ['some text', 'some text'], [true, '1'], [false, '']];
    }

    /**
     * @param  scalar  $scalarValue
     * @test
     * @dataProvider  scalarValues
     */
    public function scalarResourcesAreConvertedToOneLineCsv($scalarValue, string $expected)
    {
        assertThat(
                $this->csv->serialize($scalarValue, $this->memory)->buffer(),
                equals($expected . "\n")
        );
    }

    /**
     * @test
     */
    public function errorResourceIsConvertedToOneLineCsv()
    {
        assertThat(
                $this->csv->serialize(new Error('ups'), $this->memory)->buffer(),
                equals("Error: ups\n")
        );
    }

    /**
     * @test
     */
    public function incompatibleResourceTriggersError()
    {
        expect(function() {
                $this->csv->serialize(fopen(__FILE__, 'r'), $this->memory);
        })
                ->triggers(E_USER_ERROR)
                ->withMessage('Resource of type resource[stream] can not be serialized to csv')
                ->after($this->memory->buffer(), isEmpty());
    }

    /**
     * @test
     */
    public function serializeSimpleObjectExtractsProperties()
    {
        $object = new \stdClass();
        $object->column1 = 'bar';
        $object->column2 = 'baz';
        assertThat(
                $this->csv->serialize($object, $this->memory)->buffer(),
                equals("column1,column2\nbar,baz\n")
        );
    }

    /**
     * @test
     */
    public function serializeSimpleListWritesOneLine()
    {
        assertThat(
                $this->csv->serialize(
                        ['bar', 'baz'],
                        $this->memory
                )->buffer(),
                equals("bar,baz\n")
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
        assertThat(
                $this->csv->serialize(
                        new \ArrayIterator(['bar', 'baz']),
                        $this->memory
                )->buffer(),
                equals("bar\nbaz\n")
        );
    }

    /**
     * @test
     */
    public function serializeSimpleMapWritesHeaderLineAndOneValueLine()
    {
        assertThat(
                $this->csv->serialize(
                        ['column1' => 'bar', 'column2' => 'baz'],
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\n")
        );
    }

    /**
     * @test
     */
    public function serializeNestedArray()
    {
        assertThat(
                $this->csv->serialize(
                        [['bar', 'baz'], ['foo', 'dummy']],
                        $this->memory
                )->buffer(),
                equals("bar,baz\nfoo,dummy\n")
        );
    }

    /**
     * @test
     */
    public function serializeNestedAssociativeArray()
    {
        assertThat(
                $this->csv->serialize(
                        [['column1' => 'bar', 'column2' => 'baz'],
                         ['column1' => 'foo', 'column2' => 'dummy']
                        ],
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\nfoo,dummy\n")
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
        assertThat(
                $this->csv->serialize(
                        [$object1, $object2],
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\nfoo,dummy\n")
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
        assertThat(
                $this->csv->serialize(
                        new \ArrayIterator([$object1, $object2]),
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    /**
     * @test
     */
    public function serializeTraversable()
    {
        assertThat(
                $this->csv->serialize(
                        new \ArrayIterator(
                                [['column1' => 'bar', 'column2' => 'baz'],
                                 ['column1' => 'foo', 'column2' => 'dummy']
                                ]
                        ),
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    /**
     * @test
     */
    public function serializeNonAssociativeTraversable()
    {
        assertThat(
                $this->csv->serialize(
                        new \ArrayIterator([['bar', 'baz'], ['foo', 'dummy']]),
                        $this->memory
                )->buffer(),
                equals("bar,baz\nfoo,dummy\n")
        );
    }

    /**
     * @test
     */
    public function serializeNestedTraversable()
    {
        assertThat(
                $this->csv->serialize(
                        new \ArrayIterator(
                                [new \ArrayIterator(['column1' => 'bar', 'column2' => 'baz']),
                                 new \ArrayIterator(['column1' => 'foo', 'column2' => 'dummy'])
                                ]
                        ),
                        $this->memory
                )->buffer(),
                equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    /**
     * @test
     */
    public function supportsToArrayOnObjects()
    {
        assertThat(
                $this->csv->serialize(
                        new ToArray(),
                        $this->memory
                )->buffer(),
                equals("column1,column2\nfoo,bar\n")
        );
    }

    /**
     * @test
     */
    public function supportsNestedToArrayOnObjects()
    {
        assertThat(
                $this->csv->serialize(
                        [new ToArray(), new ToArray()],
                        $this->memory
                )->buffer(),
                equals("column1,column2\nfoo,bar\nfoo,bar\n")
        );
    }

    /**
     * @test
     */
    public function supportsAsArrayOnObjects()
    {
        assertThat(
                $this->csv->serialize(
                        new AsArray(),
                        $this->memory
                )->buffer(),
                equals("column1,column2\nfoo,bar\n")
        );
    }

    /**
     * @test
     */
    public function supportsNestedAsArrayOnObjects()
    {
        assertThat(
                $this->csv->serialize(
                        [new AsArray(), new AsArray()],
                        $this->memory
                )->buffer(),
                equals("column1,column2\nfoo,bar\nfoo,bar\n")
        );
    }

    /**
     * @test
     */
    public function serializeWithChangedDelimiter()
    {
        assertThat(
                $this->csv->changeDelimiterTo(';')
                        ->serialize(['bar', 'baz'], $this->memory)
                        ->buffer(),
                equals("bar;baz\n")
        );
    }

    /**
     * @test
     */
    public function serializeWithChangedEnclosure()
    {
        assertThat(
                $this->csv->changeEnclosureTo('/')
                        ->serialize(['bar', 'b/az'], $this->memory)
                        ->buffer(),
                equals("bar,/b//az/\n")
        );
    }
}
