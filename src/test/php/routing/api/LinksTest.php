<?php
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
/**
 * Test for stubbles\webapp\routing\api\Links.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class LinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * creates an empty links collection
     *
     * @return  \stubbles\webapp\routing\api\Links
     */
    private function createEmpty()
    {
        return new Links();
    }

    /**
     * creates a links collection with a default link
     *
     * @return  \stubbles\webapp\routing\api\Links
     */
    private function createPrefilled()
    {
        return new Links('self', HttpUri::fromString('http://example.com/foo'));
    }
    /**
     * @test
     */
    public function hasNoLinksByDefault()
    {
        assertEquals(0, count($this->createEmpty()));
    }

    /**
     * @test
     */
    public function hasLinkWhenInitiallyProvided()
    {
        assertEquals(1, count($this->createPrefilled()));
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function canNotCreatePrefilledWithoutUri()
    {
        new Links('self');
    }

    /**
     * @test
     */
    public function canAddNewLink()
    {
        assertEquals(
                1,
                count($this->createEmpty()
                        ->add(
                                'self',
                                HttpUri::fromString('http://example.com/foo')
                        )
                )
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
        assertEquals(
                '{"self":{"href":"http:\/\/example.com\/foo"},"items":[{"href":"http:\/\/example.com\/item1"},{"href":"http:\/\/example.com\/item2"},{"href":"http:\/\/example.com\/item3"}]}',
                json_encode($links)
        );
    }
}
