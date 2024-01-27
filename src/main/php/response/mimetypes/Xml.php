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
use stubbles\xml\serializer\XmlSerializerFacade;
/**
 * Serializes resources to anything/xml.
 *
 * @since  6.0.0
 */
class Xml extends MimeType
{
    public function __construct(private XmlSerializerFacade $xmlSerializerFacade) { }

    protected function defaultName(): string
    {
        return 'application/xml';
    }

    /**
     * serializes resource to output stream
     */
    public function serialize(mixed $resource, OutputStream $out): OutputStream
    {
        $out->write($this->xmlSerializerFacade->serializeToXml($resource));
        return $out;
    }
}
