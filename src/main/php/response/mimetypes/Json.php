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
 * @since  5.3.0
 */
class Json extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName()
    {
        return 'application/json';
    }

    /**
     * serializes resource to output stream
     *
     * @param  mixed  $resource
     * @param  \stubbles\streams\OutputStream $out
     */
    public function serialize($resource, OutputStream $out)
    {
        $out->write(json_encode($resource));
    }
}
