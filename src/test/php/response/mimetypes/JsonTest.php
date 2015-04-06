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
/**
 * Tests for stubbles\webapp\response\mimetypes\Json.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class JsonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Json
     */
    private $json;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->json = new Json();
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assertEquals(
                'application/json',
                (string) $this->json
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assertEquals(
                'text/json',
                (string) $this->json->specialise('text/json')
        );
    }

    /**
     * @test
     */
    public function serializesResourceToJson()
    {
        assertEquals(
                json_encode(['foo', 'bar' => 313]),
                $this->json->serialize(
                        ['foo', 'bar' => 313],
                        new MemoryOutputStream()
                )->buffer()
        );
    }
}
