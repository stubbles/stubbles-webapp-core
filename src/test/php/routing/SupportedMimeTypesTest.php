<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\AcceptHeader;
/**
 * Tests for stubbles\webapp\routing\SupportedMimeTypes.
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
        $this->assertTrue(
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     */
    public function matchForDisabledContentNegotationIsAlwaysTextHtml()
    {
        $this->assertEquals(
                'text/html',
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->findMatch(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function listOfSupportedMimeTypedWithDisabledContentNegotationIsEmpty()
    {
        $this->assertEquals(
                [],
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->asArray()
        );
    }

    /**
     * set up test environment
     */
    private function createInstance()
    {
        return new SupportedMimeTypes(
                ['application/xml', 'application/json', 'application/foo'],
                ['application/xml' => 'example\SpecialMimeType']
        );
    }

    /**
     * @test
     */
    public function contentNegotationIsEnabledWhenCreatedWithListOfMimeTypes()
    {
        $this->assertFalse($this->createInstance()->isContentNegotationDisabled());
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeFromGivenListWhenAcceptHeaderIsEmpty()
    {

        $this->assertEquals(
                'application/xml',
                $this->createInstance()->findMatch(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        $this->assertEquals(
                'application/json',
                $this->createInstance()->findMatch(
                        AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5')
                )
        );
    }

    /**
     * @test
     */
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound()
    {
        $this->assertNull(
                $this->createInstance()->findMatch(
                        AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7')
                )
        );
    }

    /**
     * @test
     */
    public function listOfSupportedMimeTypedContainsListFromCreation()
    {
        $this->assertEquals(
                ['application/xml', 'application/json', 'application/foo'],
                $this->createInstance()->asArray()
        );
    }

    /**
     * @return  array
     */
    public function predefinedMimeTypes()
    {
        return [
            ['application/json'],
            ['text/json'],
            ['text/plain'],
            ['text/xml'],
            ['application/xml'],
            ['application/rss+xml']
        ];
    }

    /**
     * @test
     * @dataProvider  predefinedMimeTypes
     * @since  5.0.0
     */
    public function hasClassForAllPredefinedMimeTypes($mimeType)
    {
        $this->assertTrue($this->createInstance()->provideClass($mimeType));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasNoClassWhenNotDefinedForMimeType()
    {
        $this->assertFalse($this->createInstance()->provideClass('application/foo'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasNoClassForUnknownMimeType()
    {
        $this->assertFalse($this->createInstance()->provideClass('application/bar'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function classIsNullWhenNotDefinedForMimeType()
    {
        $this->assertNull($this->createInstance()->classFor('application/foo'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function classIsNullForUnknownMimeType()
    {
        $this->assertNull($this->createInstance()->classFor('application/bar'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasClassWhenDefinedForMimeType()
    {
        $this->assertTrue($this->createInstance()->provideClass('application/xml'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function defaultClassCanBeOverriden()
    {
        $this->assertEquals(
                'example\SpecialMimeType',
                $this->createInstance()->classFor('application/xml')
        );
    }
}