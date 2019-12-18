<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;
/**
 * Represents a header that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='header')
 */
class Header implements \JsonSerializable
{
    /**
     * @var  string
     */
    private $name;
    /**
     * @var  string
     */
    private $description;

    /**
     * constructor
     *
     * @param  string  $name         header name
     * @param  string  $description  description of header
     */
    public function __construct(string $name, string $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    /**
     * returns header name
     *
     * @return  string
     * @XmlAttribute(attributeName='name')
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * returns description of header
     *
     * @return  string
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
