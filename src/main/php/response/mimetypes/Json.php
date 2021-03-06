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
 * Serializes resources to application/json.
 *
 * @since  6.0.0
 */
class Json extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName(): string
    {
        return 'application/json';
    }

    /**
     * serializes resource to output stream
     *
     * @template T of OutputStream
     * @param   mixed  $resource
     * @param   T      $out
     * @return  T
     */
    public function serialize($resource, OutputStream $out): OutputStream
    {
        $out->write(json_encode($resource, JSON_THROW_ON_ERROR));
        return $out;
    }
}
