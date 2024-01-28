<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use stubbles\streams\memory\MemoryOutputStream;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\TextPlain.
 *
 * @since  6.0.0
 */
#[Group('response')]
#[Group('mimetypes')]
class TextPlainTest extends TestCase
{
    private TextPlain $textPlain;

    protected function setUp(): void
    {
        $this->textPlain = new TextPlain();
    }

    #[Test]
    public function defaultMimeType(): void
    {
        assertThat((string) $this->textPlain, equals('text/plain'));
    }

    #[Test]
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
            (string) $this->textPlain->specialise('text/foo'),
            equals('text/foo')
        );
    }

    public static function serializableResources(): Generator
    {
        $stdClass = new stdClass();
        $stdClass->foo = 'bar';
        yield ['some plain text', 'some plain text'];
        yield [303, '303'];
        yield [true, 'true'];
        yield [false, 'false'];
        yield [[303 => 'cool'], "array (\n  303 => 'cool',\n)"];
        yield [$stdClass, "(object) array(\n   'foo' => 'bar',\n)"];
        yield [new TextPlain(), 'text/plain'];
    }

    #[Test]
    #[DataProvider('serializableResources')]
    public function serializesResourceToText(mixed $resource, string $expected): void
    {
        assertThat(
            $this->textPlain->serialize(
                $resource,
                new MemoryOutputStream()
            )->buffer(),
            equals($expected)
        );
    }
}
