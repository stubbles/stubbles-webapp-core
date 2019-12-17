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
 * Represents a parameter that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='parameter')
 */
class Parameter implements \JsonSerializable
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
     * @var  string
     */
    private $in;
    /**
     * @var  bool
     */
    private $required = false;

    /**
     * constructor
     *
     * @param  string  $name         header name
     * @param  string  $description  description of header
     * @param  string  $in           where parameter can be used: path, query, header, body
     */
    public function __construct(string $name, string $description, string $in)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->in          = $in;
    }

    /**
     * returns header name
     *
     * @return  string
     *
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
     * returns where parameter can be used: path, query, header, body
     *
     * @return  string
     */
    public function place(): string
    {
        return $this->in;
    }

    /**
     * marks parameter as required
     *
     * @return  \stubbles\webapp\routing\api\Parameter
     * @XmlIgnore
     */
    public function markRequired(): self
    {
        $this->required = true;
        return $this;
    }

    /**
     * checks whether parameter is required
     *
     * @return  bool
     * @XmlAttribute(attributeName='required')
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * returns representation suitable for encoding in JSON
     *
     * @return  array
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
