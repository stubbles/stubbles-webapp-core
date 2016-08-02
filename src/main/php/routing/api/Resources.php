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
/**
 * Represents the list of available resources.
 *
 * @since  6.1.0
 * @XmlTag(tagName='resources')
 */
class Resources implements \IteratorAggregate, \JsonSerializable
{
    /**
     * list of available resources
     *
     * @type  \com\oneandone\sales\uriserver\api\Resource
     */
    private $resources = [];

    /**
     * adds given resource to list of resources
     *
     * @param   \stubbles\webapp\routing\api\Resource  $resource
     * @return  \stubbles\webapp\routing\api\Resource
     */
    public function add(Resource $resource)
    {
        $this->resources[] = $resource;
        return $resource;
    }

    /**
     * allows to iterate over all resources
     *
     * @return  \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->resources);
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return $this->resources;
    }
}
