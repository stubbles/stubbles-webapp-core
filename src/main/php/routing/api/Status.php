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
 * Represents a status code that a resource may have.
 *
 * @since  6.1.0
 * @XmlTag(tagName='status')
 */
class Status implements \JsonSerializable
{
    /**
     * @type  int
     */
    private $code;
    /**
     * @type  string
     */
    private $description;

    /**
     * constructor
     *
     * @param  int     $code         actual status code
     * @param  string  $description  description of status code
     */
    public function __construct(int $code, string $description)
    {
        $this->code        = $code;
        $this->description = $description;
    }

    /**
     * returns actual status code value
     *
     * @return  int
     * @XmlAttribute(attributeName='code')
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * returns description of status code
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
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'description' => $this->description];
    }

}
