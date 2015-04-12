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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\img\Image as ImageSource;
use stubbles\img\driver\DummyDriver;
use stubbles\lang\reflect;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\response\Error;
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
        $this->resourceLoader = NewInstance::stub('stubbles\lang\ResourceLoader');
        $this->image = new Image($this->resourceLoader, 'error.png');
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        $annotations = reflect\annotationsOfConstructorParameter(
                'errorImgResource',
                $this->image
        );
        assertTrue($annotations->contain('Property'));
        assertEquals(
                'stubbles.img.error',
                $annotations->firstNamed('Property')->getName()
        );
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assertEquals(
                'image/*',
                (string) $this->image
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assertEquals(
                'image/png',
                (string) $this->image->specialise('image/png')
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
        assertEquals('fake', $dummyDriver->lastDisplayedHandle());
        callmap\verify($this->resourceLoader, 'load')->received('error.png');
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
        assertEquals('fake', $dummyDriver->lastDisplayedHandle());
        callmap\verify($this->resourceLoader, 'load')->received('pixel.png');
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
        assertEquals('fake', $dummyDriver->lastDisplayedHandle());
    }

    /**
     * @test
     * @expectedException  PHPUnit_Framework_Error
     * @expectedExceptionMessage  Can not load image "pixel.png": hm...
     */
    public function triggersUserErrorWhenImageLoadingFails()
    {
        $this->resourceLoader->mapCalls(
                ['load' => callmap\throws(new \Exception('hm...'))]
        );
        $this->image->serialize('pixel.png', new MemoryOutputStream());
    }
}
