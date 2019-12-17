<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\xml\serializer\XmlSerializerFacade;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\Xml.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class XmlTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\response\mimetypes\Xml
     */
    private $xml;
    /**
     * @var  XmlSerializerFacade&\bovigo\callmap\ClassProxy
     */
    private $xmlSerializerFacade;

    protected function setUp(): void
    {
        $this->xmlSerializerFacade = NewInstance::stub(XmlSerializerFacade::class);
        $this->xml = new Xml($this->xmlSerializerFacade);
    }

    /**
     * @test
     */
    public function defaultMimeType(): void
    {
        assertThat((string) $this->xml, equals('application/xml'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
                (string) $this->xml->specialise('text/xml'),
                equals('text/xml')
        );
    }

    /**
     * @test
     */
    public function serializesResourceToXml(): void
    {
        $this->xmlSerializerFacade->returns(['serializeToXml' => '<xml/>']);
        assertThat(
                $this->xml->serialize(
                        'value',
                        new MemoryOutputStream()
                )->buffer(),
                equals('<xml/>')
        );
    }
}
