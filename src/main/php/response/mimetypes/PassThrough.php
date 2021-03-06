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
 * Doesn't serialize resources but simply passes them through as string.
 *
 * @since  6.0.0
 */
class PassThrough extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName(): string
    {
        return 'text/html';
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
        $out->write((string) $resource);
        return $out;
    }
}
