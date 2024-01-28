<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Exception;
use GdImage;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\img\Image as ImageSource;
use stubbles\img\driver\DummyDriver;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\values\ResourceLoader;
use stubbles\webapp\response\Error;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertEmptyString;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\fail;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Tests for stubbles\webapp\response\mimetypes\Image.
 *
 * @since  6.0.0
 */
#[Group('response')]
#[Group('mimetypes')]
class ImageTest extends TestCase
{
    private Image $image;
    private ResourceLoader&ClassProxy $resourceLoader;

    protected function setUp(): void
    {
        $this->resourceLoader = NewInstance::stub(ResourceLoader::class);
        $this->image = new Image($this->resourceLoader, 'error.png');
    }

    #[Test]
    public function annotationsPresent(): void
    {
        $annotations = annotationsOfConstructorParameter(
            'errorImgResource',
            $this->image
        );
        assertTrue($annotations->contain('Property'));
        assertThat(
            $annotations->firstNamed('Property')->getName(),
            equals('stubbles.img.error')
        );
    }

    #[Test]
    public function defaultMimeType(): void
    {
        assertThat((string) $this->image, equals('image/*'));
    }

    #[Test]
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
                (string) $this->image->specialise('image/png'),
                equals('image/png')
        );
    }

    public static function emptyValues(): Generator
    {
        yield [null];
        yield [''];
    }

    #[Test]
    #[DataProvider('emptyValues')]
    public function doesNothingWhenPassedResourceIsEmpty(?string $empty): void
    {
        $out = new MemoryOutputStream();
        $this->image->serialize($empty, $out);
        assertEmptyString($out->buffer());
    }

    private function loadImage(): GdImage
    {
        $handle = imagecreatefrompng(dirname(__DIR__) . '/../../resources/' . 'empty.png');
        if (false === $handle) {
            fail('Could not create file handle');
        }

        return $handle;
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function usesErrorImgResourceWhenResourceIsError(): void
    {
        $imageHandle = $this->loadImage();
        $dummyDriver = new DummyDriver($imageHandle);
        $this->resourceLoader->returns(
            ['loadWith' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize(new Error('ups'), new MemoryOutputStream());
        assertThat($dummyDriver->lastDisplayedHandle(), equals($imageHandle));
        verify($this->resourceLoader, 'loadWith')->received('error.png');
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function displaysImageLoadedFromFilename(): void
    {
        $imageHandle = $this->loadImage();
        $dummyDriver = new DummyDriver($imageHandle);
        $this->resourceLoader->returns(
            ['loadWith' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize('pixel.png', new MemoryOutputStream());
        assertThat($dummyDriver->lastDisplayedHandle(), equals($imageHandle));
        verify($this->resourceLoader, 'loadWith')->received('pixel.png');
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function displaysImagePassedAsResource(): void
    {
        $imageHandle = $this->loadImage();
        $dummyDriver = new DummyDriver($imageHandle);
        $this->image->serialize(
            ImageSource::load('pixel.png', $dummyDriver),
            new MemoryOutputStream()
        );
        assertThat($dummyDriver->lastDisplayedHandle(), equals($imageHandle));
    }

    #[Test]
    public function triggersUserErrorWhenImageLoadingFails(): void
    {
        $this->resourceLoader->returns(
            ['loadWith' => throws(new Exception('hm...'))]
        );
        expect(function() {
            $this->image->serialize('pixel.png', new MemoryOutputStream());
        })
            ->triggers(E_USER_ERROR)
            ->withMessage('Can not load image "pixel.png": hm...');
    }
}
