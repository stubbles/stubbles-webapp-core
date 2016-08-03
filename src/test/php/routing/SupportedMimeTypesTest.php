<?php
declare(strict_types=1);
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

use function bovigo\assert\{
    assert,
    assertEmptyArray,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals
};
/**
 * Tests for stubbles\webapp\routing\SupportedMimeTypes.
 *
 * @since  2.2.0
 * @group  routing
 */
class SupportedMimeTypesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function contentNegotiationIsDisabledWhenFactoryMethodUsed()
    {
        assertTrue(
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     */
    public function matchForDisabledContentNegotationIsAlwaysTextHtml()
    {
        assert(
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->findMatch(new AcceptHeader()),
                equals('text/html')
        );
    }

    /**
     * @test
     */
    public function listOfSupportedMimeTypedWithDisabledContentNegotationIsEmpty()
    {
        assertEmptyArray(
                SupportedMimeTypes::createWithDisabledContentNegotation()
                        ->asArray()
        );
    }

    private function createInstance(): SupportedMimeTypes
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
        assertFalse($this->createInstance()->isContentNegotationDisabled());
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeFromGivenListWhenAcceptHeaderIsEmpty()
    {

        assert(
                $this->createInstance()->findMatch(new AcceptHeader()),
                equals('application/xml')
        );
    }

    /**
     * @test
     */
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        assert(
                $this->createInstance()->findMatch(
                        AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5')
                ),
                equals('application/json')
        );
    }

    /**
     * @test
     */
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound()
    {
        assertNull(
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
        assert(
                $this->createInstance()->asArray(),
                equals(['application/xml', 'application/json', 'application/foo'])
        );
    }

    public function predefinedMimeTypes(): array
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
    public function hasClassForAllPredefinedMimeTypes(string $mimeType)
    {
        assertTrue($this->createInstance()->provideClass($mimeType));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasNoClassWhenNotDefinedForMimeType()
    {
        assertFalse($this->createInstance()->provideClass('application/foo'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasNoClassForUnknownMimeType()
    {
        assertFalse($this->createInstance()->provideClass('application/bar'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function classIsNullWhenNotDefinedForMimeType()
    {
        assertNull($this->createInstance()->classFor('application/foo'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function classIsNullForUnknownMimeType()
    {
        assertNull($this->createInstance()->classFor('application/bar'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function hasClassWhenDefinedForMimeType()
    {
        assertTrue($this->createInstance()->provideClass('application/xml'));
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function defaultClassCanBeOverriden()
    {
        assert(
                $this->createInstance()->classFor('application/xml'),
                equals('example\SpecialMimeType')
        );
    }
}
