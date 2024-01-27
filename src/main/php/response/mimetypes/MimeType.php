<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\streams\OutputStream;
/**
 * Represents mime types.
 *
 * @since  6.0.0
 */
abstract class MimeType
{
    private ?string $name = null;

    /**
     * returns default mime type name
     */
    abstract protected function defaultName(): string;

    /**
     * specialises to specific mime type
     */
    public function specialise(string $mimeType): self
    {
        $this->name = $mimeType;
        return $this;
    }

    /**
     * serializes resource to output stream
     *
     * It returns the output stream that was passed.
     */
    abstract public function serialize(
        mixed $resource,
        OutputStream $out
    ): OutputStream;

    /**
     * returns string representation of mime type
     */
    public function __toString(): string
    {
        return null !== $this->name ? $this->name : $this->defaultName();
    }
}
