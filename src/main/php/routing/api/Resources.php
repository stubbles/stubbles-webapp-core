<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Represents the list of available resources.
 *
 * @since  6.1.0
 * @XmlTag(tagName='resources')
 * @implements  \IteratorAggregate<Resource>
 */
class Resources implements IteratorAggregate, JsonSerializable
{
    /** @var  Resource[] */
    private array $resources = [];

    /**
     * adds given resource to list of resources
     */
    public function add(Resource $resource): Resource
    {
        $this->resources[] = $resource;
        return $resource;
    }

    /**
     * allows to iterate over all resources
     *
     * @return  \Iterator<Resource>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->resources);
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  Resource[]
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return $this->resources;
    }
}
