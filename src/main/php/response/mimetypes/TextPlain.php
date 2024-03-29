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
class TextPlain extends MimeType
{
    protected function defaultName(): string
    {
        return 'text/plain';
    }

    /**
     * serializes resource to output stream
     */
    public function serialize(mixed $resource, OutputStream $out): OutputStream
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

        return $out;
    }
}
