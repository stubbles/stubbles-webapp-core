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
use stubbles\streams\memory\MemoryOutputStream;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\TextPlain.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class TextPlainTest extends TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\TextPlain
     */
    private $textPlain;

    protected function setUp(): void
    {
        $this->textPlain = new TextPlain();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assertThat((string) $this->textPlain, equals('text/plain'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assertThat(
                (string) $this->textPlain->specialise('text/foo'),
                equals('text/foo')
        );
    }

    public function serializableResources(): array
    {
        $stdClass = new \stdClass();
        $stdClass->foo = 'bar';
        return [
            ['some plain text', 'some plain text'],
            [303, '303'],
            [true, 'true'],
            [false, 'false'],
            [[303 => 'cool'], "array (\n  303 => 'cool',\n)"],
            [$stdClass, "(object) array(\n   'foo' => 'bar',\n)"],
            [new TextPlain(), 'text/plain']
        ];
    }

    /**
     * @test
     * @dataProvider  serializableResources
     */
    public function serializesResourceToText($resource, string $expected)
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
