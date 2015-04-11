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
use bovigo\callmap\NewInstance;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request  = NewInstance::of('stubbles\webapp\Request');
        $this->response = NewInstance::of('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = reflect\annotationsOfConstructor(
                'stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader'
        );
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
        $this->apply($emptyConfig);
        assertEquals(0, $this->response->callsReceivedFor('addHeader'));
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader()
    {
        $this->request->mapCalls(['hasHeader' => false]);
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        assertEquals(0, $this->response->callsReceivedFor('addHeader'));
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenOriginFromRequestDoesNotMatchAllowedOriginHosts()
    {
        $this->request->mapCalls(
                ['hasHeader'  => true,
                 'readHeader' => ValueReader::forValue('http://example.net')
                ]
        );
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        assertEquals(0, $this->response->callsReceivedFor('addHeader'));
    }

    /**
     * @test
     */
    public function addsHeaderWhenOriginFromRequestIsAllowed()
    {
        $this->request->mapCalls(
                ['hasHeader'  => true,
                 'readHeader' => ValueReader::forValue('http://foo.example.com:9039')
                ]
        );
        $this->apply(
                '^http://[a-zA-Z0-9-\.]+example\.net(:[0-9]{4})?$'
                . '|^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$'
        );
        assertEquals(
                ['Access-Control-Allow-Origin', 'http://foo.example.com:9039'],
                $this->response->argumentsReceivedFor('addHeader')
        );
    }
}
