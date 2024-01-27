<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private Links $links;

    /**
     * constructor
     *
     * @param  string|null         $name            name of resource
     * @param  string[]            $requestMethods  list of possible request methods
     * @param  HttpUri             $selfUri         uri under which resource is available
     * @param  string[]            $mimeTypes       list of supported mime types
     * @param  RoutingAnnotations  $annotations     list of annotations on resource
     * @param  AuthConstraint      $authConstraint  authentication and authorization constraints of resource
     */
    public function __construct(
        private ?string $name,
        private array $requestMethods,
        HttpUri $selfUri,
        private array $mimeTypes,
        private RoutingAnnotations $annotations,
        private AuthConstraint $authConstraint)
    {
        $this->links = new Links('self', $selfUri);
    }

    /**
     * returns name of resource
     *
     * @XmlAttribute(attributeName='name')
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * returns list of allowed request methods
     *
     * @return  string[]
     */
    public function requestMethods(): array
    {
        return $this->requestMethods;
    }

    /**
     * checks whether resource has a description
     *
     * @XmlIgnore
     */
    public function hasDescription(): bool
    {
        return null !== $this->annotations->description();
    }

    /**
     * returns description of resource
     *
     * @XmlTag(tagName='description')
     */
    public function description(): ?string
    {
        return $this->annotations->description();
    }

    public function addLink(string $rel, string|HttpUri $uri): Link
    {
        return $this->links->add($rel, HttpUri::castFrom($uri));
    }

    /**
     * returns uri path where resource is available
     *
     * @XmlTag(tagName='links')
     */
    public function links(): Links
    {
        return $this->links;
    }

    /**
     * checks if any mime types are defined for this resource
     *
     * @XmlIgnore
     */
    public function hasMimeTypes(): bool
    {
        return count($this->mimeTypes) > 0;
    }

    /**
     * returns list of mime types supported by this resource
     *
     * @return  string[]
     * @XmlTag(tagName='produces', elementTagName='mimetype')
     */
    public function mimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * checks if information about status codes is provided
     *
     * @XmlIgnore
     */
    public function providesStatusCodes(): bool
    {
        return $this->annotations->containStatusCodes();
    }

    /**
     * returns list of possible response status codes
     *
     * @return  Status[]
     * @XmlTag(tagName='responses')
     */
    public function statusCodes(): array
    {
        return $this->annotations->statusCodes();
    }

    /**
     * checks if information about response headers is provided
     *
     * @XmlIgnore
     */
    public function hasHeaders(): bool
    {
        return $this->annotations->containHeaders();
    }

    /**
     * returns list of possible response headers
     *
     * @return  Header[]
     * @XmlTag(tagName='headers')
     */
    public function headers(): array
    {
        return $this->annotations->headers();
    }

    /**
     * checks if information about parameters is provided
     *
     * @XmlIgnore
     */
    public function hasParameters(): bool
    {
        return $this->annotations->containParameters();
    }

    /**
     * returns list of parameters that can be used for on this resource
     *
     * @return  Parameter[]
     * @XmlTag(tagName='parameters')
     */
    public function parameters(): array
    {
        return $this->annotations->parameters();
    }

    /**
     * returns auth constraint of resource
     *
     * @XmlTag(tagName='auth')
     */
    public function authConstraint(): AuthConstraint
    {
        return $this->authConstraint;
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array<string,mixed>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
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
