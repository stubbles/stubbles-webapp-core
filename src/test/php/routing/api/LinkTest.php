<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\routing\api\Link.
 *
 * @since  6.1.0
 */
#[Group('routing')]
#[Group('routing_api')]
class LinkTest extends TestCase
{
    private Link $link;

    protected function setUp(): void
    {
        $this->link = new Link(
            'self',
            HttpUri::fromString('http://example.com/foo')
        );
    }

    #[Test]
    public function returnsProvidedRel(): void
    {
        assertThat($this->link->rel(), equals('self'));
    }

    #[Test]
    public function returnsProvidedUri(): void
    {
        assertThat($this->link->uri(), equals('http://example.com/foo'));
    }

    #[Test]
    public function stringRepresentationIsUri(): void
    {
        assertThat($this->link, equals('http://example.com/foo'));
    }

    #[Test]
    public function canBeSerializedToJson(): void
    {
        assertThat(
            json_encode($this->link),
            equals('{"href":"http:\/\/example.com\/foo"}')
        );
    }
}
