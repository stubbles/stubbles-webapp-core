<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\routing\api\Link.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class LinkTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\api\Link
     */
    private $link;

    protected function setUp(): void
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
        assertThat($this->link->rel(), equals('self'));
    }

    /**
     * @test
     */
    public function returnsProvidedUri()
    {
        assertThat($this->link->uri(), equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function stringRepresentationIsUri()
    {
        assertThat($this->link, equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        assertThat(
                json_encode($this->link),
                equals('{"href":"http:\/\/example.com\/foo"}')
        );
    }
}
