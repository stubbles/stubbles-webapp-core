<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\interceptor;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\input\ValueReader;
use stubbles\webapp\Request;
use stubbles\webapp\Response;

use function bovigo\assert\assertThat;
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
class AddAccessControlAllowOriginHeaderTest extends TestCase
{
    /**
     * @var  Request&\bovigo\callmap\ClassProxy
     */
    private $request;
    /**
     * @var  Response&\bovigo\callmap\ClassProxy
     */
    private $response;

    protected function setUp(): void
    {
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor(): void
    {
        $annotations = annotationsOfConstructor(
                AddAccessControlAllowOriginHeader::class
        );
        assertTrue($annotations->contain('Property'));
        assertThat(
                $annotations->firstNamed('Property')->getValue(),
                equals('stubbles.webapp.origin.hosts')
        );
    }

    /**
     * @param  string|string[]  $config
     */
    private function apply($config): void
    {
        (new AddAccessControlAllowOriginHeader($config))
                ->postProcess($this->request, $this->response);
    }

    /**
     * @return  array<mixed[]>
     */
    public static function emptyConfigs(): array
    {
        return [[''], [[]]];
    }

    /**
     * @param  mixed  $emptyConfig
     * @test
     * @dataProvider  emptyConfigs
     */
    public function doesNotAddHeaderWhenNoAllowedOriginHostConfigured($emptyConfig): void
    {
        $this->request->returns(['hasHeader' => false]);
        $this->apply($emptyConfig);
        assertTrue(verify($this->response, 'addHeader')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader(): void
    {
        $this->request->returns(['hasHeader' => false]);
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        assertTrue(verify($this->response, 'addHeader')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsEmptyOriginHeader(): void
    {
        $this->request->returns([
            'hasHeader'  => true,
            'readHeader' => ValueReader::forValue('')
        ]);
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        assertTrue(verify($this->response, 'addHeader')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenOriginFromRequestDoesNotMatchAllowedOriginHosts(): void
    {
        $this->request->returns([
                'hasHeader'  => true,
                'readHeader' => ValueReader::forValue('http://example.net')

        ]);
        $this->apply('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        assertTrue(verify($this->response, 'addHeader')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function addsHeaderWhenOriginFromRequestIsAllowed(): void
    {
        $this->request->returns([
                'hasHeader'  => true,
                'readHeader' => ValueReader::forValue('http://foo.example.com:9039')

        ]);
        $this->apply(
                '^http://[a-zA-Z0-9-\.]+example\.net(:[0-9]{4})?$'
                . '|^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$'
        );
        assertTrue(verify($this->response, 'addHeader')->received(
                'Access-Control-Allow-Origin',
                'http://foo.example.com:9039'
        ));
    }
}
