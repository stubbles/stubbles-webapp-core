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
use stubbles\webapp\auth\AuthConstraint;
use stubbles\webapp\routing\RoutingAnnotations;
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
     * list of allowed request methods for this resource
     *
     * @type  string[]
     */
    private $requestMethods;
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
     * list of annotations on resource
     *
     * @type  \stubbles\webapp\routing\RoutingAnnotations
     */
    private $annotations;
    /**
     * authentication and authorization constraints of resource
     *
     * @type  \stubbles\webapp\auth\AuthConstraint
     */
    private $authConstraint;

    /**
     * constructor
     *
     * @param  string                                       $name            name of resource
     * @param  string[]                                     $requestMethods  list of possible request methods
     * @param  \stubbles\peer\http\HttpUri                  $selfUri         uri under which resource is available
     * @param  string[]                                     $mimeTypes       list of supported mime types
     * @param  \stubbles\webapp\routing\RoutingAnnotations  $annotations     list of annotations on resource
     * @param  \stubbles\webapp\auth\AuthConstraint         $authConstraint  authentication and authorization constraints of resource
     */
    public function __construct(
            $name,
            array $requestMethods,
            HttpUri $selfUri,
            array $mimeTypes,
            RoutingAnnotations $annotations,
            AuthConstraint $authConstraint)
    {
        $this->name           = $name;
        $this->requestMethods = $requestMethods;
        $this->links          = new Links('self', $selfUri);
        $this->mimeTypes      = $mimeTypes;
        $this->annotations    = $annotations;
        $this->authConstraint = $authConstraint;
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
     * returns list of allowed request methods
     *
     * @return  string[]
     */
    public function requestMethods()
    {
        return $this->requestMethods;
    }

    /**
     * checks whether resource has a description
     *
     * @return  bool
     * @XmlIgnore
     */
    public function hasDescription()
    {
        return null !== $this->annotations->description();
    }

    /**
     * returns description of resource
     *
     * @return  string
     * @XmlTag(tagName='description')
     */
    public function description()
    {
        return $this->annotations->description();
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
     * checks if any mime types are defined for this resource
     *
     * @return  bool
     * @XmlIgnore
     */
    public function hasMimeTypes()
    {
        return count($this->mimeTypes) > 0;
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
     * checks if information about status codes is provided
     *
     * @return  bool
     * @XmlIgnore
     */
    public function providesStatusCodes()
    {
        return $this->annotations->containStatusCodes();
    }

    /**
     * returns list of possible response status codes
     *
     * @return  \stubbles\webapp\routing\api\Status[]
     * @XmlTag(tagName='responses')
     */
    public function statusCodes()
    {
        return $this->annotations->statusCodes();
    }

    /**
     * checks if information about response headers is provided
     *
     * @return  bool
     * @XmlIgnore
     */
    public function hasHeaders()
    {
        return $this->annotations->containHeaders();
    }

    /**
     * returns list of possible response headers
     *
     * @return  \stubbles\webapp\routing\api\Header[]
     * @XmlTag(tagName='headers')
     */
    public function headers()
    {
        return $this->annotations->headers();
    }

    /**
     * checks if information about parameters is provided
     *
     * @return  bool
     * @XmlIgnore
     */
    public function hasParameters()
    {
        return $this->annotations->containParameters();
    }

    /**
     * returns list of parameters that can be used for on this resource
     *
     * @return  \stubbles\webapp\routing\api\Parameter[]
     * @XmlTag(tagName='parameters')
     */
    public function parameters()
    {
        return $this->annotations->parameters();
    }

    /**
     * returns auth constraint of resource
     *
     * @return  \stubbles\webapp\auth\AuthConstraint
     * @XmlTag(tagName='auth')
     */
    public function authConstraint()
    {
        return $this->authConstraint;
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
                'methods'     => $this->requestMethods,
                'description' => $this->annotations->description(),
                'produces'    => $this->mimeTypes,
                'responses'   => $this->annotations->statusCodes(),
                'headers'     => $this->annotations->headers(),
                'parameters'  => $this->annotations->parameters(),
                'auth'        => $this->authConstraint,
                '_links'      => $this->links
        ];
    }
}

