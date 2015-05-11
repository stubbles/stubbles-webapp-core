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
 * Represents a single resource.
 *
 * @since  6.1.0
 * @XmlTag(tagName='resource')
 */
class Resource implements \JsonSerializable
{
    /**
     * @type  string
     */
    private $name;
    /**
     * @type  string
     */
    private $description;
    /**
     * @type  \stubbles\webapp\routing\api\Links
     */
    private $links;
    /**
     * list of mime types supported by this resource
     *
     * @type  string[]
     */
    private $mimeTypes;

    /**
     * constructor
     *
     * @param  string                       $name         name of resource
     * @param  string                       $description  description of resource
     * @param  \stubbles\peer\http\HttpUri  $selfUri
     * @param  string[]                     $mimeTypes    list of supported mime types
     */
    public function __construct(
            $name,
            $description,
            HttpUri $selfUri,
            array $mimeTypes)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->links       = new Links('self', $selfUri);
        $this->mimeTypes   = $mimeTypes;
    }

    /**
     * returns name of resource
     *
     * @return  string
     * @XmlAttribute(attributeName='name')
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * checks whether resource has a description
     *
     * @return  bool
     * @XmlIgnore
     */
    public function hasDescription()
    {
        return null !== $this->description;
    }

    /**
     * returns description of resource
     *
     * @return  string
     * @XmlTag(tagName='description')
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * adds a link for this resource
     *
     * @param   string  $rel  relation of this link to the resource
     * @param   string  $uri  actual uri
     * @return  \stubbles\webapp\routing\api\Link
     */
    public function addLink($rel, $uri)
    {
        return $this->links->add($rel, $uri);
    }

    /**
     * returns uri path where resource is available
     *
     * @return  \stubbles\webapp\routing\api\Links
     * @XmlTag(tagName='links')
     */
    public function links()
    {
        return $this->links;
    }

    /**
     * returns list of mime types supported by this resource
     *
     * @return  string[]
     * @XmlTag(tagName='mimetypes', elementTagName='mimetype')
     */
    public function mimeTypes()
    {
        return $this->mimeTypes;
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return [
                'name'        => $this->name,
                'description' => $this->description,
                'mimetypes'   => $this->mimeTypes,
                '_links'      => $this->links
        ];
    }
}

