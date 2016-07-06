<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use bovigo\callmap\NewInstance;
use stubbles\img\Image as ImageSource;
use stubbles\img\driver\DummyDriver;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\values\ResourceLoader;
use stubbles\webapp\response\Error;

use function bovigo\assert\assert;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
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
class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Image
     */
    private $image;
    /**
     * @type  \bovigo\callmap\Proxy
     */
    private $resourceLoader;


    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->resourceLoader = NewInstance::stub(ResourceLoader::class);
        $this->image = new Image($this->resourceLoader, 'error.png');
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        $annotations = annotationsOfConstructorParameter(
                'errorImgResource',
                $this->image
        );
        assertTrue($annotations->contain('Property'));
        assert(
                $annotations->firstNamed('Property')->getName(),
                equals('stubbles.img.error')
        );
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assert((string) $this->image, equals('image/*'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assert(
                (string) $this->image->specialise('image/png'),
                equals('image/png')
        );
    }

    /**
     * @return  array
     */
    public function emptyValues()
    {
        return [[null], ['']];
    }

    /**
     * @test
     * @dataProvider  emptyValues
     */
    public function doesNothingWhenPassedResourceIsEmpty($empty)
    {
        $this->image->serialize($empty, new MemoryOutputStream());
    }

    /**
     * @test
     */
    public function usesErrorImgResourceWhenResourceIsError()
    {
        $dummyDriver = new DummyDriver('fake');
        $this->resourceLoader->mapCalls(
                ['load' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize(new Error('ups'), new MemoryOutputStream());
        assert($dummyDriver->lastDisplayedHandle(), equals('fake'));
        verify($this->resourceLoader, 'load')->received('error.png');
    }

    /**
     * @test
     */
    public function displaysImageLoadedFromFilename()
    {
        $dummyDriver = new DummyDriver('fake');
        $this->resourceLoader->mapCalls(
                ['load' => ImageSource::load('error.png', $dummyDriver)]
        );
        $this->image->serialize('pixel.png', new MemoryOutputStream());
        assert($dummyDriver->lastDisplayedHandle(), equals('fake'));
        verify($this->resourceLoader, 'load')->received('pixel.png');
    }

    /**
     * @test
     */
    public function displaysImagePassedAsResource()
    {
        $dummyDriver = new DummyDriver('fake');
        $this->image->serialize(
                ImageSource::load(
                        'pixel.png',
                        $dummyDriver
                ),
                new MemoryOutputStream()
        );
        assert($dummyDriver->lastDisplayedHandle(), equals('fake'));
    }

    /**
     * @test
     */
    public function triggersUserErrorWhenImageLoadingFails()
    {
        $this->resourceLoader->mapCalls(
                ['load' => throws(new \Exception('hm...'))]
        );
        expect(function() {
                $this->image->serialize('pixel.png', new MemoryOutputStream());
        })
                ->throws(\PHPUnit_Framework_Error::class)
                ->withMessage('Can not load image "pixel.png": hm...');
    }
}
