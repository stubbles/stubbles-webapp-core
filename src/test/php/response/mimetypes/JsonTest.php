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
        assert((string) $this->json, equals('application/json'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assert(
                (string) $this->json->specialise('text/json'),
                equals('text/json')
        );
    }

    /**
     * @test
     */
    public function serializesResourceToJson()
    {
        assert(
                $this->json->serialize(
                        ['foo', 'bar' => 313],
                        new MemoryOutputStream()
                )->buffer(),
                equals(json_encode(['foo', 'bar' => 313]))
        );
    }
}
