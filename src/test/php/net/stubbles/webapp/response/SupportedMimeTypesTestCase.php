<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
use net\stubbles\peer\http\AcceptHeader;
/**
 * Tests for net\stubbles\webapp\response\SupportedMimeTypes.
 *
 * @since  2.2.0
 * @group  response
 */
class SupportedMimeTypesTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function contentNegotiationIsDisabledWhenFactoryMethodUsed()
    {
        $this->assertTrue(SupportedMimeTypes::createWithDisabledContentNegotation()
                                            ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     */
    public function matchForDisabledContentNegotationIsAlwaysTextHtml()
    {
        $this->assertEquals('text/html',
                            SupportedMimeTypes::createWithDisabledContentNegotation()
                                              ->findMatch(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function listOfSupportedMimeTypedWithDisabledContentNegotationIsEmpty()
    {
        $this->assertEquals(array(),
                            SupportedMimeTypes::createWithDisabledContentNegotation()
                                              ->asArray()
        );
    }

    /**
     * set up test environment
     */
    private function createInstance()
    {
        return new SupportedMimeTypes(array('application/xml', 'application/json'));
    }

    /**
     * @test
     */
    public function contentNegotationIsEnabledWhenCreatedWithListOfMimeTypes()
    {
        $this->assertFalse($this->createInstance()
                                ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeFromGivenListWhenAcceptHeaderIsEmpty()
    {

        $this->assertEquals('application/xml',
                            $this->createInstance()
                                 ->findMatch(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        $this->assertEquals('application/json',
                            $this->createInstance()
                                 ->findMatch(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound()
    {
        $this->assertNull($this->createInstance()
                               ->findMatch(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7'))
        );
    }

    /**
     * @test
     */
    public function listOfSupportedMimeTypedContainsListFromCreation()
    {
        $this->assertEquals(array('application/xml', 'application/json'),
                            $this->createInstance()
                                 ->asArray()
        );
    }
}