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

use function bovigo\assert\{
    assertThat,
    assertEmpty,
    assertEmptyArray,
    expect,
    predicate\equals,
    predicate\isOfSize
};
/**
 * Test for stubbles\webapp\routing\api\Links.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class LinksTest extends TestCase
{
    private function createPrefilled(): Links
    {
        return new Links('self', HttpUri::fromString('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function hasNoLinksByDefault()
    {
        assertEmpty(new Links());
    }

    /**
     * @test
     */
    public function hasLinkWhenInitiallyProvided()
    {
        assertThat($this->createPrefilled(), isOfSize(1));
    }

    /**
     * @test
     */
    public function canNotCreatePrefilledWithoutUri()
    {
        expect(function() { new Links('self'); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function canAddNewLink()
    {
        $links = new Links();
        $links->add('self', HttpUri::fromString('http://example.com/foo'));
        assertThat($links, isOfSize(1));
    }

    /**
     * @test
     */
    public function relWithoutLinks()
    {
        assertEmptyArray((new Links())->with('self'));
    }

    /**
     * @test
     */
    public function relWithOneLink()
    {
        $links = new Links();
        $links->add(
                'self',
                HttpUri::fromString('http://example.com/foo')
        );
        assertThat(
                $links->with('self'),
                equals([new Link('self', HttpUri::fromString('http://example.com/foo'))])
        );
    }

    /**
     * @test
     */
    public function relWithSeveralLinks()
    {
        $links = new Links();
        $links->add(
                'other',
                HttpUri::fromString('http://example.com/foo')
        );
        $links->add(
                'other',
                HttpUri::fromString('http://example.com/bar')
        );
        assertThat(
                $links->with('other'),
                equals([
                        new Link('other', HttpUri::fromString('http://example.com/foo')),
                        new Link('other', HttpUri::fromString('http://example.com/bar'))
                ])
        );
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        $links = $this->createPrefilled();
        $links->add(
                'items',
                HttpUri::fromString('http://example.com/item1')
        );
        $links->add(
                'items',
                HttpUri::fromString('http://example.com/item2')
        );
        $links->add(
                'items',
                HttpUri::fromString('http://example.com/item3')
        );
        assertThat(
                json_encode($links),
                equals('{"self":{"href":"http:\/\/example.com\/foo"},"items":[{"href":"http:\/\/example.com\/item1"},{"href":"http:\/\/example.com\/item2"},{"href":"http:\/\/example.com\/item3"}]}')
        );
    }
}
