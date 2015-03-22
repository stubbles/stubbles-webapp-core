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
 * Serializes resources to application/json.
 *
 * @since  6.0.0
 */
class TextPlain extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName()
    {
        return 'text/plain';
    }

    /**
     * serializes resource to output stream
     *
     * @param  mixed  $resource
     * @param  \stubbles\streams\OutputStream $out
     */
    public function serialize($resource, OutputStream $out)
    {
        if (is_object($resource) && method_exists($resource, '__toString')) {
            $out->write((string) $resource);
        } elseif (is_object($resource) || is_array($resource)) {
            $out->write(var_export($resource, true));
        } elseif (is_bool($resource) && $resource) {
            $out->write('true');
        } elseif (is_bool($resource) && !$resource) {
            $out->write('false');
        } else {
            $out->write((string) $resource);
        }
    }
}
