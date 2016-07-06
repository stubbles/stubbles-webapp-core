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

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\TextPlain.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class TextPlainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\TextPlain
     */
    private $textPlain;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->textPlain = new TextPlain();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assert((string) $this->textPlain, equals('text/plain'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assert(
                (string) $this->textPlain->specialise('text/foo'),
                equals('text/foo')
        );
    }

    /**
     * @return  array
     */
    public function serializableResources()
    {
        $stdClass = new \stdClass();
        $stdClass->foo = 'bar';
        return [
            ['some plain text', 'some plain text'],
            [303, '303'],
            [true, 'true'],
            [false, 'false'],
            [[303 => 'cool'], "array (\n  303 => 'cool',\n)"],
            [$stdClass, "stdClass::__set_state(array(\n   'foo' => 'bar',\n))"],
            [new TextPlain(), 'text/plain']
        ];
    }

    /**
     * @test
     * @dataProvider  serializableResources
     */
    public function serializesResourceToText($resource, $expected)
    {
        assert(
                $this->textPlain->serialize(
                        $resource,
                        new MemoryOutputStream()
                )->buffer(),
                equals($expected)
        );
    }
}
