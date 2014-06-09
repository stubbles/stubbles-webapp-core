<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\AcceptHeader;
/**
 * Tests for stubbles\webapp\response\SupportedMimeTypes.
 *
 * @since  2.2.0
 * @group  response
 */
class SupportedMimeTypesTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals([],
                            SupportedMimeTypes::createWithDisabledContentNegotation()
                                              ->asArray()
        );
    }

    /**
     * set up test environment
     */
    private function createInstance()
    {
        return new SupportedMimeTypes(['application/xml', 'application/json'],
                                      ['application/xml' => 'example\SpecialFormatter']
        );
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
        $this->assertEquals(['application/xml', 'application/json'],
                            $this->createInstance()
                                 ->asArray()
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasNoSpecialFormatterWhenNonDefinedForMimeType()
    {
        $this->assertFalse($this->createInstance()->provideFormatter('application/json'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function specialFormatterClassIsNullWhenNonDefinedForMimeType()
    {
        $this->assertNull($this->createInstance()->formatterFor('application/json'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasSpecialFormatterWhenDefinedForMimeType()
    {
        $this->assertTrue($this->createInstance()->provideFormatter('application/xml'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function specialFormatterClassIsEqualsDefinedForMimeType()
    {
        $this->assertEquals('example\SpecialFormatter',
                            $this->createInstance()->formatterFor('application/xml')
        );
    }
}