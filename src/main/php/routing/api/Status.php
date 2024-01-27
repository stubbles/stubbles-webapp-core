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
     * @param  int     $code         actual status code
     * @param  string  $description  description of status code
     */
    public function __construct(private int $code, private string $description) { }

    /**
     * returns actual status code value
     *
     * @XmlAttribute(attributeName='code')
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * returns description of status code
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
     * @return  array<string,scalar>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'description' => $this->description];
    }
}
