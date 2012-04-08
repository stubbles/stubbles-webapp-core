<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
/**
 * Tests for net\stubbles\webapp\UriConfiguration.
 *
 * @since  1.7.0
 * @group  core
 */
class UriConfigurationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  UriConfiguration
     */
    private $uriConfiguration;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->uriConfiguration = new UriConfiguration('example\\DefaultProcessor',
                                                       array('example\\SomePreInterceptor'        => null,
                                                             'example\\OtherPreInterceptor'       => '^/xml/?',
                                                             'example\\UnavailablePreInterceptor' => '^/rest/'
                                                       ),
                                                       array('^/xml/?'   => 'example\\XmlProcessor',
                                                             '^/users'  => 'example\\RestProcessor',
                                                             '^/admins' => 'example\\RestProcessor'
                                                       ),
                                                       array('example\\SomePostInterceptor'        => null,
                                                             'example\\OtherPostInterceptor'       => '^/xml/?',
                                                             'example\\UnavailablePostInterceptor' => '^/rest/'
                                                       )
                                   );
    }

    /**
     * @test
     */
    public function returnsOnlyApplicablePreInterceptors()
    {
        $this->assertEquals(array('example\\SomePreInterceptor',
                                  'example\\OtherPreInterceptor'
                            ),
                            $this->uriConfiguration->getPreInterceptors(UriRequest::fromString('http://example.net/xml/Home'))
        );
    }

    /**
     * @test
     */
    public function returnsOnlyApplicablePostInterceptors()
    {
        $this->assertEquals(array('example\\SomePostInterceptor',
                                  'example\\OtherPostInterceptor'
                            ),
                            $this->uriConfiguration->getPostInterceptors(UriRequest::fromString('http://example.net/xml/Home'))
        );
    }

    /**
     * @test
     */
    public function returnsDefaultProcessorIfNoOtherProcessorSatisfiesUriRequest()
    {
        $this->assertEquals('example\\DefaultProcessor',
                            $this->uriConfiguration->getProcessorForUri(UriRequest::fromString('http://example.net/more'))
        );
    }

    /**
     * @test
     */
    public function doesNotFillProcessorUriWhenDefaultProcessorSelected()
    {
        $uriRequest = UriRequest::fromString('http://example.net/more');
        $this->uriConfiguration->getProcessorForUri($uriRequest);
        $this->assertEquals('/', $uriRequest->getProcessorUri());
    }

    /**
     * @test
     */
    public function returnsProcessorWhichSatisfiesUriRequest()
    {
        $this->assertEquals('example\\RestProcessor',
                            $this->uriConfiguration->getProcessorForUri(UriRequest::fromString('http://example.net/users/1'))
        );
    }

    /**
     * @test
     */
    public function fillsProcessorUriWhenProcessorSelected()
    {
        $uriRequest = UriRequest::fromString('http://example.net/users/1');
        $this->uriConfiguration->getProcessorForUri($uriRequest);
        $this->assertEquals('/users', $uriRequest->getProcessorUri());
    }
}
?>