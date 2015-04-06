<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\interceptor;
use stubbles\input\ValueReader;
use stubbles\lang\reflect;
/**
 * Tests for stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader.
 *
 * @since  3.4.0
 * @group  interceptor
 */
class AddAccessControlAllowOriginHeaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = reflect\annotationsOfConstructor(
                'stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader'
        );
        assertTrue($annotations->contain('Inject'));
        assertTrue($annotations->contain('Property'));
        assertEquals(
                'stubbles.webapp.origin.hosts',
                $annotations->firstNamed('Property')->getValue()
        );
    }

    /**
     * creates instance for test
     *
     * @param   string  $config
     * @return  \stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader
     */
    private function apply($config)
    {
        $foo = new AddAccessControlAllowOriginHeader($config);
        $foo->postProcess($this->request, $this->response);
    }

    public function emptyConfigs()
    {
        return [[null], [''], [[]]];
    }

    /**
     * @test
     * @dataProvider  emptyConfigs
     */
    public function doesNotAddHeaderWhenNoAllowedOriginHostConfigured($emptyConfig)
    {
        $this->response->expects(never())->method('addHeader');
        $this->apply($emptyConfig);
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader()
    {
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(false));
        $this->response->expects(never())->method('addHeader');
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenOriginFromRequestDoesNotMatchAllowedOriginHosts()
    {
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(true));
        $this->request->expects(any())
                ->method('readHeader')
                ->will(returnValue(ValueReader::forValue('http://example.net')));
        $this->response->expects(never())->method('addHeader');
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
    }

    /**
     * @test
     */
    public function addsHeaderWhenOriginFromRequestIsAllowed()
    {
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(true));
        $this->request->expects(any())
                ->method('readHeader')
                ->will(returnValue(ValueReader::forValue('http://foo.example.com:9039')));
        $this->response->expects(once())
                ->method('addHeader')
                ->with(
                        equalTo('Access-Control-Allow-Origin'),
                        equalTo('http://foo.example.com:9039')
                );
        $this->apply(
                '^http://[a-zA-Z0-9-\.]+example\.net(:[0-9]{4})?$'
                . '|^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$'
        );
    }
}
