<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing\api;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\routing\api\Link.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class LinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\api\Link
     */
    private $link;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->link = new Link(
                'self',
                HttpUri::fromString('http://example.com/foo')
        );
    }

    /**
     * @test
     */
    public function returnsProvidedRel()
    {
        assert($this->link->rel(), equals('self'));
    }

    /**
     * @test
     */
    public function returnsProvidedUri()
    {
        assert($this->link->uri(), equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function stringRepresentationIsUri()
    {
        assert($this->link, equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        assert(
                json_encode($this->link),
                equals('{"href":"http:\/\/example.com\/foo"}')
        );
    }
}
