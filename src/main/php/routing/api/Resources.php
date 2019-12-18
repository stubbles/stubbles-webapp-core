<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;
/**
 * Represents the list of available resources.
 *
 * @since  6.1.0
 * @XmlTag(tagName='resources')
 * @implements  \IteratorAggregate<\stubbles\webapp\routing\api\Resource>
 */
class Resources implements \IteratorAggregate, \JsonSerializable
{
    /**
     * list of available resources
     *
     * @var  \stubbles\webapp\routing\api\Resource[]
     */
    private $resources = [];

    /**
     * adds given resource to list of resources
     *
     * @param   \stubbles\webapp\routing\api\Resource  $resource
     * @return  \stubbles\webapp\routing\api\Resource
     */
    public function add(Resource $resource): Resource
    {
        $this->resources[] = $resource;
        return $resource;
    }

    /**
     * allows to iterate over all resources
     *
     * @return  \Iterator<\stubbles\webapp\routing\api\Resource>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->resources);
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  \stubbles\webapp\routing\api\Resource[]
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return $this->resources;
    }
}
