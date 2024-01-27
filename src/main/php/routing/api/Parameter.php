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
 * Represents a parameter that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='parameter')
 */
class Parameter implements JsonSerializable
{
    private bool $required = false;

    /**
     * @param  string  $name         parameter name
     * @param  string  $description  description of parameter
     * @param  string  $in           where parameter can be used: path, query, header, body
     */
    public function __construct(
        private string $name,
        private string $description,
        private string $in
    ) { }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @XmlFragment(tagName='description')
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * returns where parameter can be used: path, query, header, body
     */
    public function place(): string
    {
        return $this->in;
    }

    /**
     * @XmlIgnore
     */
    public function markRequired(): self
    {
        $this->required = true;
        return $this;
    }

    /**
     * @XmlAttribute(attributeName='required')
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * returns representation suitable for encoding in JSON
     *
     * @return  array<string,scalar>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'place'       => $this->in,
            'required'    => $this->required
        ];
    }

}
