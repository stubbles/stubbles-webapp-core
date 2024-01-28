<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * @since  6.0.0
 */
#[Group('response')]
#[Group('mimetypes')]
class CsvTest extends TestCase
{
    private Csv $csv;
    private MemoryOutputStream $memory;

    protected function setUp(): void
    {
        $this->csv    = new Csv();
        $this->memory = new MemoryOutputStream();
    }

    #[Test]
    public function defaultMimeType(): void
    {
        assertThat((string) $this->csv, equals('text/csv'));
    }

    #[Test]
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
            (string) $this->csv->specialise('text/vendor-csv'),
            equals('text/vendor-csv')
        );
    }

    public static function scalarValues(): Generator
    {
        yield [1, '1'];
        yield ['some text', 'some text'];
        yield [true, '1'];
        yield [false, ''];
    }

    /**
     * @param  scalar  $scalarValue
     */
    #[Test]
    #[DataProvider('scalarValues')]
    public function scalarResourcesAreConvertedToOneLineCsv($scalarValue, string $expected): void
    {
        assertThat(
            $this->csv->serialize($scalarValue, $this->memory)->buffer(),
            equals($expected . "\n")
        );
    }

    #[Test]
    public function errorResourceIsConvertedToOneLineCsv(): void
    {
        assertThat(
            $this->csv->serialize(new Error('ups'), $this->memory)->buffer(),
            equals("Error: ups\n")
        );
    }

    #[Test]
    public function incompatibleResourceTriggersError(): void
    {
        expect(function() {
            $this->csv->serialize(fopen(__FILE__, 'r'), $this->memory);
        })
            ->triggers(E_USER_ERROR)
            ->withMessage('Resource of type resource[stream] can not be serialized to csv')
            ->after($this->memory->buffer(), isEmpty());
    }

    #[Test]
    public function serializeSimpleObjectExtractsProperties(): void
    {
        $object = new \stdClass();
        $object->column1 = 'bar';
        $object->column2 = 'baz';
        assertThat(
            $this->csv->serialize($object, $this->memory)->buffer(),
            equals("column1,column2\nbar,baz\n")
        );
    }

    #[Test]
    public function serializeSimpleListWritesOneLine(): void
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
     * This behaviour is different then the one for array because for a
     * \Traversable we can not inspect the first element to check if it is
     * something iterable or just a scalar value as we can with arrays.
     */
    #[Test]
    public function serializeSimpleTravsersableListWritesOneLineForEachEntry(): void
    {
        assertThat(
            $this->csv->serialize(
                new ArrayIterator(['bar', 'baz']),
                $this->memory
            )->buffer(),
            equals("bar\nbaz\n")
        );
    }

    #[Test]
    public function serializeSimpleMapWritesHeaderLineAndOneValueLine(): void
    {
        assertThat(
            $this->csv->serialize(
                ['column1' => 'bar', 'column2' => 'baz'],
                $this->memory
            )->buffer(),
            equals("column1,column2\nbar,baz\n")
        );
    }

    #[Test]
    public function serializeNestedArray(): void
    {
        assertThat(
            $this->csv->serialize(
                [['bar', 'baz'], ['foo', 'dummy']],
                $this->memory
            )->buffer(),
            equals("bar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function serializeNestedAssociativeArray(): void
    {
        assertThat(
            $this->csv->serialize(
                [
                    ['column1' => 'bar', 'column2' => 'baz'],
                    ['column1' => 'foo', 'column2' => 'dummy']
                ],
                $this->memory
            )->buffer(),
            equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function serializeListOfObjects(): void
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

    #[Test]
    public function serializeTraversableOfObjects(): void
    {
        $object1 = new \stdClass();
        $object1->column1 = 'bar';
        $object1->column2 = 'baz';
        $object2 = new \stdClass();
        $object2->column1 = 'foo';
        $object2->column2 = 'dummy';
        assertThat(
            $this->csv->serialize(
                new ArrayIterator([$object1, $object2]),
                $this->memory
            )->buffer(),
            equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function serializeTraversable(): void
    {
        assertThat(
            $this->csv->serialize(
                new ArrayIterator([
                    ['column1' => 'bar', 'column2' => 'baz'],
                    ['column1' => 'foo', 'column2' => 'dummy']
                ]),
                $this->memory
            )->buffer(),
            equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function serializeNonAssociativeTraversable(): void
    {
        assertThat(
            $this->csv->serialize(
                new ArrayIterator([['bar', 'baz'], ['foo', 'dummy']]),
                $this->memory
            )->buffer(),
            equals("bar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function serializeNestedTraversable(): void
    {
        assertThat(
            $this->csv->serialize(
                new ArrayIterator([
                    new ArrayIterator(['column1' => 'bar', 'column2' => 'baz']),
                    new ArrayIterator(['column1' => 'foo', 'column2' => 'dummy'])
                ]),
                $this->memory
            )->buffer(),
            equals("column1,column2\nbar,baz\nfoo,dummy\n")
        );
    }

    #[Test]
    public function supportsToArrayOnObjects(): void
    {
        assertThat(
            $this->csv->serialize(new ToArray(), $this->memory)->buffer(),
            equals("column1,column2\nfoo,bar\n")
        );
    }

    #[Test]
    public function supportsNestedToArrayOnObjects(): void
    {
        assertThat(
            $this->csv->serialize(
                [new ToArray(), new ToArray()],
                $this->memory
            )->buffer(),
            equals("column1,column2\nfoo,bar\nfoo,bar\n")
        );
    }

    #[Test]
    public function supportsAsArrayOnObjects(): void
    {
        assertThat(
            $this->csv->serialize(new AsArray(), $this->memory)->buffer(),
            equals("column1,column2\nfoo,bar\n")
        );
    }

    #[Test]
    public function supportsNestedAsArrayOnObjects(): void
    {
        assertThat(
            $this->csv->serialize(
                [new AsArray(), new AsArray()],
                $this->memory
            )->buffer(),
            equals("column1,column2\nfoo,bar\nfoo,bar\n")
        );
    }

    #[Test]
    public function serializeWithChangedDelimiter(): void
    {
        assertThat(
            $this->csv->changeDelimiterTo(';')
                ->serialize(['bar', 'baz'], $this->memory)
                ->buffer(),
            equals("bar;baz\n")
        );
    }

    #[Test]
    public function serializeWithChangedEnclosure(): void
    {
        assertThat(
            $this->csv->changeEnclosureTo('/')
                ->serialize(['bar', 'b/az'], $this->memory)
                ->buffer(),
            equals("bar,/b//az/\n")
        );
    }
}
