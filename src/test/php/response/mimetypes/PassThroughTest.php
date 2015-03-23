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
        $this->assertEquals(
                'text/html',
                (string) $this->passThrough
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        $this->assertEquals(
                'text/plain',
                (string) $this->passThrough->specialise('text/plain')
        );
    }

    /**
     * @test
     */
    public function serializesPassesThroughString()
    {
        $this->assertEquals(
                'some string',
                $this->passThrough->serialize(
                        'some string',
                        new MemoryOutputStream()
                )->buffer()
        );
    }

    /**
     * @test
     */
    public function serializesHandlesErrorAsString()
    {
        $this->assertEquals(
                'Error: some error message',
                $this->passThrough->serialize(
                        new Error('some error message'),
                        new MemoryOutputStream()
                )->buffer()
        );
    }
}
