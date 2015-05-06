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
 * Represents all links for a resource.
 *
 * @since  6.1.0
 * @XmlTag(tagName='links')
 */
class Links implements \IteratorAggregate, \JsonSerializable, \Countable
{
    /**
     * @type  \stubbles\webapp\routing\api\Link
     */
    private $links = [];

    /**
     * constructor
     *
     * @param   string                       $rel  relation of this link to the resource  optional
     * @param   \stubbles\peer\http\HttpUri  $uri  uri for this relation                  optional
     * @throws  \InvalidArgumentException  in case $uri is empty but $rel is not
     */
    public function __construct($rel = null, HttpUri $uri = null)
    {
        if (null !== $rel) {
            if (null === $uri) {
                throw new \InvalidArgumentException(
                        'Uri is null, but rel "' . $rel . "' given"
                );
            }

            $this->add($rel, $uri);
        }
    }

    /**
     * adds link to collection of links
     *
     * @param   string                       $rel
     * @param   \stubbles\peer\http\HttpUri  $uri
     * @return  \stubbles\webapp\routing\api\Link
     */
    public function add($rel, HttpUri $uri)
    {
        $link = new Link($rel, $uri);
        if (isset($this->links[$rel])) {
            if (!is_array($this->links[$rel])) {
                $this->links[$rel] = [$this->links[$rel]];
            }

            $this->links[$rel][] = $link;
        } else {
            $this->links[$rel] = $link;
        }

        return $link;
    }

    /**
     * allows to iterate over all resources
     *
     * @return  \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->links);
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return $this->links;
    }

    /**
     * returns amount of links
     *
     * @return  int
     * @XmlIgnore
     */
    public function count()
    {
        return count($this->links);
    }
}
