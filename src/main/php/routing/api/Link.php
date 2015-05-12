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
 * Represents a link for a resource in a certain environment.
 *
 * @todo   add support for optional properties
 * @see    https://tools.ietf.org/html/draft-kelly-json-hal-06
 * @since  6.1.0
 * @XmlTag(tagName='link')
 */
class Link implements \JsonSerializable
{
    /**
     * relation of this link
     *
     * @type  string
     */
    private $rel;
    /**
     * @type  \stubbles\peer\http\HttpUri
     */
    private $uri;

    /**
     * constructor
     *
     * @param  string                       $rel  relation of this link to the resource
     * @param  \stubbles\peer\http\HttpUri  $uri  actual uri
     */
    public function __construct($rel, HttpUri $uri)
    {
        $this->rel = $rel;
        $this->uri = $uri;
    }

    /**
     * returns how this link relates to the resource
     *
     * @XmlAttribute(attributeName='rel')
     * @return  string
     */
    public function rel()
    {
        return $this->rel;
    }

    /**
     * returns uri
     *
     * @XmlAttribute(attributeName='href')
     * @return  string
     */
    public function uri()
    {
        return $this->uri->asStringWithNonDefaultPort();
    }

    /**
     * returns string representation
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->uri->asStringWithNonDefaultPort();
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return ['href' => $this->uri->asString()];
    }
}
