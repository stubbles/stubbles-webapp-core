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

/**
 * Represents a header that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='header')
 */
class Header implements JsonSerializable
{
    public function __construct(private string $name, private string $description) { }

    /**
     * returns header name
     *
     * @XmlAttribute(attributeName='name')
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * returns description of header
     *
     * @XmlFragment(tagName='description')
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * returns representation suitable for encoding in JSON
     *
     * @return  array<string,string>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'description' => $this->description];
    }
}
