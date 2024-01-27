<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use JsonSerializable;
use stubbles\peer\http\HttpUri;
/**
 * Represents a link for a resource in a certain environment.
 *
 * @todo   add support for optional properties
 * @see    https://tools.ietf.org/html/draft-kelly-json-hal-06
 * @since  6.1.0
 * @XmlTag(tagName='link')
 */
class Link implements JsonSerializable
{
    /**
     * @param  string   $rel  relation of this link to the resource
     * @param  HttpUri  $uri  actual uri
     */
    public function __construct(private string $rel, private HttpUri $uri) { }

    /**
     * returns how this link relates to the resource
     *
     * @XmlAttribute(attributeName='rel')
     */
    public function rel(): string
    {
        return $this->rel;
    }

    /**
     * @XmlAttribute(attributeName='href')
     */
    public function uri(): string
    {
        return $this->uri->asStringWithNonDefaultPort();
    }

    public function __toString(): string
    {
        return $this->uri->asStringWithNonDefaultPort();
    }

    /**
     * returns proper representation which can be serialized to JSON
     *
     * @return  array<string,string>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['href' => $this->uri->asString()];
    }
}
