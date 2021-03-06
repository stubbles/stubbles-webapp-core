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
    /**
     * serializer to be used
     *
     * @var  \stubbles\xml\serializer\XmlSerializerFacade
     */
    private $xmlSerializerFacade;

    /**
     * constructor
     *
     * @param  \stubbles\xml\serializer\XmlSerializerFacade  $xmlSerializerFacade
     */
    public function __construct(XmlSerializerFacade $xmlSerializerFacade)
    {
        $this->xmlSerializerFacade = $xmlSerializerFacade;
    }

    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName(): string
    {
        return 'application/xml';
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
        $out->write($this->xmlSerializerFacade->serializeToXml($resource));
        return $out;
    }
}
