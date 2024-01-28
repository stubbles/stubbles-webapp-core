<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('routing')]
#[Group('routing_api')]
class LinksTest extends TestCase
{
    private function createPrefilled(): Links
    {
        return new Links('self', HttpUri::fromString('http://example.com/foo'));
    }

    #[Test]
    public function hasNoLinksByDefault(): void
    {
        assertEmpty(new Links());
    }

    #[Test]
    public function hasLinkWhenInitiallyProvided(): void
    {
        assertThat($this->createPrefilled(), isOfSize(1));
    }

    #[Test]
    public function canNotCreatePrefilledWithoutUri(): void
    {
        expect(fn() => new Links('self'))
            ->throws(InvalidArgumentException::class);
    }

    #[Test]
    public function canAddNewLink(): void
    {
        $links = new Links();
        $links->add('self', HttpUri::fromString('http://example.com/foo'));
        assertThat($links, isOfSize(1));
    }

    #[Test]
    public function relWithoutLinks(): void
    {
        assertEmptyArray((new Links())->with('self'));
    }

    #[Test]
    public function relWithOneLink(): void
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

    #[Test]
    public function relWithSeveralLinks(): void
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

    #[Test]
    public function canBeSerializedToJson(): void
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
