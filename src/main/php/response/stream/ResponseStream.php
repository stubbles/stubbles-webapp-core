<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\stream;
use stubbles\streams\OutputStream;
/**
 * @since  5.3.0
 */
abstract class ResponseStream
{
    /**
     * @type  \stubbles\streams\OutputStream
     */
    private $outputStream;
    /**
     * @type  string
     */
    private $mimeType;

    /**
     * constructor
     *
     * @param  string                          $mimeType      mime type the stream represents
     * @param  \stubbles\streams\OutputStream  $outputStream  optipnal  output stream to write to
     */
    public function __construct($mimeType, OutputStream $outputStream = null)
    {
        $this->mimeType     = $mimeType;
        $this->outputStream = (null === $outputStream ? new StandardOutputStream() : $outputStream);
    }

    /**
     * returns mime type of current stream
     *
     * @return  string
     */
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * stream the given resource
     *
     * @param  mixed  $resource
     */
    public abstract function stream($resource);

    /**
     * writes given bytes
     *
     * @param   string  $bytes
     * @return  int     amount of written bytes
     */
    protected function write($bytes)
    {
        return $this->outputStream->write($bytes);
    }

    /**
     * writes given bytes and appends a line break
     *
     * @param   string  $bytes
     * @return  int     amount of written bytes
     */
    protected function writeLine($bytes)
    {
        return $this->outputStream->writeLine($bytes);
    }

    /**
     * writes given bytes and appends a line break after each one
     *
     * @param   string[]  $bytes
     * @return  int       amount of written bytes
     */
    protected function writeLines(array $bytes)
    {
        return $this->outputStream->writeLines($bytes);
    }
}
