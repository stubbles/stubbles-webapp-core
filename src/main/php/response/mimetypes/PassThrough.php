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
    protected function defaultName(): string
    {
        return 'text/html';
    }

    /**
     * serializes resource to output stream
     */
    public function serialize(mixed $resource, OutputStream $out): OutputStream
    {
        $out->write((string) $resource);
        return $out;
    }
}
