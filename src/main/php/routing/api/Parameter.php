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
 * Represents a parameter that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='parameter')
 */
class Parameter implements \JsonSerializable
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
     * @type  string
     */
    private $in;
    /**
     * @type  bool
     */
    private $required = false;

    /**
     * constructor
     *
     * @param  int     $name         header name
     * @param  string  $description  description of header
     * @param  string  $in           where parameter can be used: path, query, header, body
     */
    public function __construct($name, $description, $in)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->in          = $in;
    }

    /**
     * returns header name
     *
     * @return  int
     * 
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
     * returns where parameter can be used: path, query, header, body
     *
     * @return  string
     */
    public function place()
    {
        return $this->in;
    }

    /**
     * marks parameter as required
     *
     * @return  \stubbles\webapp\routing\api\Parameter
     * @XmlIgnore
     */
    public function markRequired()
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
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * returns representation suitable for encoding in JSON
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return [
                'name'        => $this->name,
                'description' => $this->description,
                'place'       => $this->in,
                'required'    => $this->required
        ];
    }

}
