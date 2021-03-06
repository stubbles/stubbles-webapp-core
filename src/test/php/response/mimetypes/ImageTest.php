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
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class ImageTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\response\mimetypes\Image
     */
    private $image;
    /**
     * @var  ResourceLoader&\bovigo\callmap\ClassProxy
     */
    private $resourceLoader;

    protected function setUp(): void
    {
        $this->resourceLoader = NewInstance::stub(ResourceLoader::class);
        $this->image = new Image($this->resourceLoader, 'error.png');
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function defaultMimeType(): void
    {
        assertThat((string) $this->image, equals('image/*'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
                (string) $this->image->specialise('image/png'),
                equals('image/png')
        );
    }

    /**
     * @return  array<mixed[]>
     */
    public function emptyValues(): array
    {
        return [[null], ['']];
    }

    /**
     * @param  mixed  $empty
     * @test
     * @dataProvider  emptyValues
     */
    public function doesNothingWhenPassedResourceIsEmpty($empty): void
    {
        $out = new MemoryOutputStream();
        $this->image->serialize($empty, $out);
        assertEmptyString($out->buffer());
    }

    /**
     * @test
     */
    public function usesErrorImgResourceWhenResourceIsError(): void
    {
        $handle = imagecreatefrompng(dirname(__DIR__) . '/../../resources/' . 'empty.png');
        if (false === $handle) {
            fail('Could not create file handle');
        }

        $dummyDriver = new DummyDriver($handle);
        $this->resourceLoader->returns(
                ['load' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize(new Error('ups'), new MemoryOutputStream());
        assertThat($dummyDriver->lastDisplayedHandle(), equals($handle));
        verify($this->resourceLoader, 'load')->received('error.png');
    }

    /**
     * @test
     */
    public function displaysImageLoadedFromFilename(): void
    {
        $handle = imagecreatefrompng(dirname(__DIR__) . '/../../resources/' . 'empty.png');
        if (false === $handle) {
            fail('Could not create file handle');
        }

        $dummyDriver = new DummyDriver($handle);
        $this->resourceLoader->returns(
                ['load' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize('pixel.png', new MemoryOutputStream());
        assertThat($dummyDriver->lastDisplayedHandle(), equals($handle));
        verify($this->resourceLoader, 'load')->received('pixel.png');
    }

    /**
     * @test
     */
    public function displaysImagePassedAsResource(): void
    {
        $handle = imagecreatefrompng(dirname(__DIR__) . '/../../resources/' . 'empty.png');
        if (false === $handle) {
            fail('Could not create file handle');
        }

        $dummyDriver = new DummyDriver($handle);
        $this->image->serialize(
                ImageSource::load(
                        'pixel.png',
                        $dummyDriver
                ),
                new MemoryOutputStream()
        );
        assertThat($dummyDriver->lastDisplayedHandle(), equals($handle));
    }

    /**
     * @test
     */
    public function triggersUserErrorWhenImageLoadingFails(): void
    {
        $this->resourceLoader->returns(
                ['load' => throws(new \Exception('hm...'))]
        );
        expect(function() {
                $this->image->serialize('pixel.png', new MemoryOutputStream());
        })
                ->triggers(E_USER_ERROR)
                ->withMessage('Can not load image "pixel.png": hm...');
    }
}
