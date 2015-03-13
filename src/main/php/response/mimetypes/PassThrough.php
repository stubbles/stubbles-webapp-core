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
 * Doesn't serialize resources but simply passes them through as string.
 *
 * @since  5.3.0
 */
class PassThrough extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName()
    {
        return 'text/html';
    }

    /**
     * serializes resource to output stream
     *
     * @param  mixed  $resource
     * @param  \stubbles\streams\OutputStream $out
     */
    public function serialize($resource, OutputStream $out)
    {
        if (is_array($resource) && isset($resource['error'])) {
            $out->write($resource['error']);
        } else {
            $out->write((string) $resource);
        }
    }
}
