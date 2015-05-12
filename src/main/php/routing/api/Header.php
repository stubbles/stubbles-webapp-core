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
/**
 * Represents a header that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='header')
 */
class Header implements \JsonSerializable
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
     *
     * @param  int     $name         header name
     * @param  strint  $description  description of header
     */
    public function __construct($name, $description)
    {
        $this->name        = $name;
        $this->description = $description;
    }

    /**
     * returns header name
     *
     * @return  int
     * @XmlAttribute(attributeName='name')
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * returns description of header
     *
     * @return  string
     * @XmlFragment(tagName='description')
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * returns representation suitable for encoding in JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return ['name' => $this->name, 'description' => $this->description];
    }

}
