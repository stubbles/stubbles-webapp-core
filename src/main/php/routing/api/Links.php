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
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use stubbles\peer\http\HttpUri;
use Traversable;

/**
 * Represents all links for a resource.
 *
 * @since  6.1.0
 * @XmlTag(tagName='links')
 * @implements  IteratorAggregate<Link>
 */
class Links implements IteratorAggregate, JsonSerializable, Countable
{
    /** @var  array<string,Link|Link[]> */
    private array $links = [];

    /**
     * @param   string   $rel  relation of this link to the resource
     * @param   HttpUri  $uri  uri for this relation
     * @throws  InvalidArgumentException  in case $uri is empty but $rel is not
     */
    public function __construct(?string $rel = null, ?HttpUri $uri = null)
    {
        if (null !== $rel) {
            if (null === $uri) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Uri is null, but rel "%s" given',
                        $rel
                    )
                );
            }

            $this->add($rel, $uri);
        }
    }

    public function add(string $rel, HttpUri $uri): Link
    {
        $link = new Link($rel, $uri);
        if (isset($this->links[$rel])) {
            if (!is_array($this->links[$rel])) {
                $this->links[$rel] = [$this->links[$rel], $link];
            } else {
                $this->links[$rel][] = $link;
            }
        } else {
            $this->links[$rel] = $link;
        }

        return $link;
    }

    /**
     * returns all links with given relation
     *
     * @return  Link[]
     */
    public function with(string $rel): array
    {
        if (isset($this->links[$rel])) {
            if (is_array($this->links[$rel])) {
                return $this->links[$rel];
            }

            return [$this->links[$rel]];
        }

        return [];
    }

    /**
     * allows to iterate over all resources
     *
     * @return  \Iterator<Link>
     */
    public function getIterator(): Traversable
    {
        $result = [];
        foreach ($this->links as $link) {
            if (is_array($link)) {
                $result = array_merge($result, $link);
            } else {
                $result[] = $link;
            }
        }

        return new ArrayIterator($result);
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array<Link|Link[]>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return $this->links;
    }

    /**
     * @XmlIgnore
     */
    public function count(): int
    {
        return count($this->links);
    }
}
