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
use stubbles\img\Image as ImageSource;
use stubbles\img\driver\DummyDriver;
use stubbles\lang\reflect;
use stubbles\streams\memory\MemoryOutputStream;
/**
 * Tests for stubbles\webapp\response\mimetypes\Image.
 *
 * @group  response
 * @group  mimetypes
 * @since  5.4.0
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Image
     */
    private $image;
    /**
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResourceLoader;


    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockResourceLoader = $this->getMockBuilder('stubbles\lang\ResourceLoader')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->image = new Image($this->mockResourceLoader);
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->image)
                        ->contain('Inject')
        );
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        $this->assertEquals(
                'image/*',
                (string) $this->image
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        $this->assertEquals(
                'image/png',
                (string) $this->image->specialise('image/png')
        );
    }

    /**
     * @test
     */
    public function displaysImageLoadedFromFilename()
    {
        $dummyDriver = new DummyDriver('fake');
        $this->mockResourceLoader->expects($this->once())
                                 ->method('load')
                                 ->with($this->equalTo('pixel.png'))
                                 ->will($this->returnValue(
                                        ImageSource::load(
                                                'pixel.png',
                                                $dummyDriver
                                        )
                                ));
        $this->image->serialize(
                'pixel.png',
                new MemoryOutputStream()
        );
        $this->assertEquals('fake', $dummyDriver->lastDisplayedHandle());
    }

    /**
     * @test
     */
    public function displaysImagePassedAsResource()
    {
        $dummyDriver = new DummyDriver('fake');
        $this->mockResourceLoader->expects($this->never())
                                 ->method('load');
        $this->image->serialize(
                ImageSource::load(
                        'pixel.png',
                        $dummyDriver
                ),
                new MemoryOutputStream()
        );
        $this->assertEquals('fake', $dummyDriver->lastDisplayedHandle());
    }
}
