<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\streams\OutputStream;
/**
 * Represents mime types.
 *
 * @since  5.3.0
 */
abstract class MimeType
{
    /**
     * @type  string
     */
    private $name = null;

    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected abstract function defaultName();

    /**
     * specialises to specific mime type
     *
     * @param   string  $mimeType
     * @return  \stubbles\webapp\response\mimetypes\MimeType
     */
    public function specialise($mimeType)
    {
        $this->name = $mimeType;
        return $this;
    }

    /**
     * serializes resource to output stream
     *
     * It returns the output stream that was passed.
     *
     * @param   mixed  $resource
     * @param   \stubbles\streams\OutputStream  $out
     * @return  \stubbles\streams\OutputStream
     */
    public abstract function serialize($resource, OutputStream $out);

    /**
     * returns string representation of mime type
     *
     * @return  string
     */
    public function __toString()
    {
        return null !== $this->name ? $this->name : $this->defaultName();
    }
}
