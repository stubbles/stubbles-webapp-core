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

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\PassThrough.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class PassThroughTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\PassThrough
     */
    private $passThrough;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->passThrough = new PassThrough();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assert((string) $this->passThrough, equals('text/html'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assert(
                (string) $this->passThrough->specialise('text/plain'),
                equals('text/plain')
        );
    }

    /**
     * @test
     */
    public function serializesPassesThroughString()
    {
        assert(
                $this->passThrough->serialize(
                        'some string',
                        new MemoryOutputStream()
                )->buffer(),
                equals('some string')
        );
    }

    /**
     * @test
     */
    public function serializesHandlesErrorAsString()
    {
        assert(
                $this->passThrough->serialize(
                        new Error('some error message'),
                        new MemoryOutputStream()
                )->buffer(),
                equals('Error: some error message')
        );
    }
}
