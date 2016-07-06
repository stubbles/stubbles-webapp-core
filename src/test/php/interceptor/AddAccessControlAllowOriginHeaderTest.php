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
use stubbles\webapp\Request;
use stubbles\webapp\Response;

use function bovigo\assert\assert;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructor;
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
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = annotationsOfConstructor(
                AddAccessControlAllowOriginHeader::class
        );
        assertTrue($annotations->contain('Property'));
        assert(
                $annotations->firstNamed('Property')->getValue(),
                equals('stubbles.webapp.origin.hosts')
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
        verify($this->response, 'addHeader')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader()
    {
        $this->request->mapCalls(['hasHeader' => false]);
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        verify($this->response, 'addHeader')->wasNeverCalled();
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
        verify($this->response, 'addHeader')->wasNeverCalled();
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
        verify($this->response, 'addHeader')
                ->received(
                        'Access-Control-Allow-Origin',
                        'http://foo.example.com:9039'
        );
    }
}
