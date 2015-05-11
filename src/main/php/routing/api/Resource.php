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
     * map of possible response status codes
     *
     * @type  stubbles\webapp\routing\api\Status[]
     */
    private $statusCodes;

    /**
     * constructor
     *
     * @param  string                                $name         name of resource
     * @param  string                                $description  description of resource
     * @param  \stubbles\peer\http\HttpUri           $selfUri      uri under which resource is available
     * @param  string[]                              $mimeTypes    list of supported mime types
     * @param  stubbles\webapp\routing\api\Status[]  $statusCodes  map of possible response status codes
     */
    public function __construct(
            $name,
            $description,
            HttpUri $selfUri,
            array $mimeTypes,
            array $statusCodes)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->links       = new Links('self', $selfUri);
        $this->mimeTypes   = $mimeTypes;
        $this->statusCodes = $statusCodes;
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
     * @XmlTag(tagName='produces', elementTagName='mimetype')
     */
    public function mimeTypes()
    {
        return $this->mimeTypes;
    }

    /**
     * returns map of possible response status codes
     *
     * @return  stubbles\webapp\routing\api\Status[]
     * @XmlTag(tagName='responses')
     */
    public function statusCodes()
    {
        return $this->statusCodes;
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
                'produces'    => $this->mimeTypes,
                'responses'   => $this->statusCodes,
                '_links'      => $this->links
        ];
    }
}

