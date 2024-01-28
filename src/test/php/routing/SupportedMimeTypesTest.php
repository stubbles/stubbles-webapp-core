<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\AcceptHeader;

use function bovigo\assert\{
    assertThat,
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
class SupportedMimeTypesTest extends TestCase
{
    #[Test]
    public function contentNegotiationIsDisabledWhenFactoryMethodUsed(): void
    {
        assertTrue(
            SupportedMimeTypes::createWithDisabledContentNegotation()
                ->isContentNegotationDisabled()
        );
    }

    #[Test]
    public function matchForDisabledContentNegotationIsAlwaysTextHtml(): void
    {
        assertThat(
            SupportedMimeTypes::createWithDisabledContentNegotation()
                ->findMatch(new AcceptHeader()),
            equals('text/html')
        );
    }

    #[Test]
    public function listOfSupportedMimeTypedWithDisabledContentNegotationIsEmpty(): void
    {
        assertEmptyArray(
            SupportedMimeTypes::createWithDisabledContentNegotation()->asArray()
        );
    }

    private function createInstance(): SupportedMimeTypes
    {
        return new SupportedMimeTypes(
            ['application/xml', 'application/json', 'application/foo'],
            ['application/xml' => 'stubbles\webapp\response\mimetypes\Xml']
        );
    }

    #[Test]
    public function contentNegotationIsEnabledWhenCreatedWithListOfMimeTypes(): void
    {
        assertFalse($this->createInstance()->isContentNegotationDisabled());
    }

    #[Test]
    public function returnsFirstMimeTypeFromGivenListWhenAcceptHeaderIsEmpty(): void
    {
        assertThat(
            $this->createInstance()->findMatch(new AcceptHeader()),
            equals('application/xml')
        );
    }

    #[Test]
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader(): void
    {
        assertThat(
            $this->createInstance()->findMatch(
                AcceptHeader::parse(
                    'text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'
                )
            ),
            equals('application/json')
        );
    }

    #[Test]
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound(): void
    {
        assertNull(
            $this->createInstance()->findMatch(
                AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7')
            )
        );
    }

    #[Test]
    public function listOfSupportedMimeTypedContainsListFromCreation(): void
    {
        assertThat(
            $this->createInstance()->asArray(),
            equals(['application/xml', 'application/json', 'application/foo'])
        );
    }

    public static function providePredefinedMimeTypes(): Generator
    {
        yield ['application/json'];
        yield ['text/json'];
        yield ['text/plain'];
        yield ['text/xml'];
        yield ['application/xml'];
        yield ['application/rss+xml'];
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    #[DataProvider('providePredefinedMimeTypes')]
    public function hasClassForAllPredefinedMimeTypes(string $mimeType): void
    {
        assertTrue($this->createInstance()->provideClass($mimeType));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function hasNoClassWhenNotDefinedForMimeType(): void
    {
        assertFalse($this->createInstance()->provideClass('application/foo'));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function hasNoClassForUnknownMimeType(): void
    {
        assertFalse($this->createInstance()->provideClass('application/bar'));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function classIsNullWhenNotDefinedForMimeType(): void
    {
        assertNull($this->createInstance()->classFor('application/foo'));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function classIsNullForUnknownMimeType(): void
    {
        assertNull($this->createInstance()->classFor('application/bar'));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function hasClassWhenDefinedForMimeType(): void
    {
        assertTrue($this->createInstance()->provideClass('application/xml'));
    }

    /**
     * @since  3.2.0
     */
    #[Test]
    public function defaultClassCanBeOverriden(): void
    {
        assertThat(
            $this->createInstance()->classFor('application/xml'),
            equals('stubbles\webapp\response\mimetypes\Xml')
        );
    }

    public static function provideImageMimetypes(): Generator
    {
        yield ['image/png'];
        yield ['image/jpeg'];
    }

    /**
     * @since  8.1.0
     */
    #[Test]
    #[DataProvider('provideImageMimetypes')]
    public function supportsImageMimeTypesWhenStubblesImagePresent(string $imageMimetype): void
    {
        assertTrue($this->createInstance()->provideClass($imageMimetype));
    }
}
