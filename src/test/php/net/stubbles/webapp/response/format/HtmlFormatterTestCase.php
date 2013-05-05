<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response\format;
use net\stubbles\lang;
/**
 * Tests for net\stubbles\webapp\response\format\HtmlFormatter.
 *
 * @since  2.0.0
 * @group  response
 * @group  format
 */
class HtmlFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  HtmlFormatter
     */
    private $htmlFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->htmlFormatter = new HtmlFormatter();
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetTemplateMethod()
    {
        $method = lang\reflect($this->htmlFormatter, 'setTemplate');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
        $this->assertTrue($method->hasAnnotation('Named'));
        $this->assertEquals('net.stubbles.webapp.response.format.html.template',
                            $method->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetBaseTitleMethod()
    {
        $method = lang\reflect($this->htmlFormatter, 'setBaseTitle');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
        $this->assertTrue($method->hasAnnotation('Named'));
        $this->assertEquals('net.stubbles.webapp.response.format.html.title',
                            $method->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function formatArrayWithoutTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title></title></head><body><h1>Hello</h1><p>Hello world!</p></body></html>',
                            $this->htmlFormatter->format(array('content' => '<h1>Hello</h1><p>Hello world!</p>'))
        );
    }

    /**
     * @test
     */
    public function formatArrayWithBaseTitleWithoutTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>Cool Web App</title></head><body><h1>Hello</h1><p>Hello world!</p></body></html>',
                            $this->htmlFormatter->setBaseTitle('Cool Web App')
                                                ->format(array('content' => '<h1>Hello</h1><p>Hello world!</p>'))
        );
    }

    /**
     * @test
     */
    public function formatArrayWithTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>Hello world</title><meta name="robots" content="index, follow"/></head><body><h1>Hello</h1><p>Hello world!</p></body></html>',
                            $this->htmlFormatter->format(array('title'   => 'Hello world',
                                                               'meta'    => '<meta name="robots" content="index, follow"/>',
                                                               'content' => '<h1>Hello</h1><p>Hello world!</p>'))
        );
    }

    /**
     * @test
     */
    public function formatArrayWithBaseTitleAndTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>Cool Web App Hello world</title><meta name="robots" content="index, follow"/></head><body><h1>Hello</h1><p>Hello world!</p></body></html>',
                            $this->htmlFormatter->setBaseTitle('Cool Web App')
                                                ->format(array('title'   => 'Hello world',
                                                               'meta'    => '<meta name="robots" content="index, follow"/>',
                                                               'content' => '<h1>Hello</h1><p>Hello world!</p>'))
        );
    }

    /**
     * @test
     */
    public function formatOtherWithoutBaseTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title></title></head><body>foo bar baz</body></html>',
                            $this->htmlFormatter->format('foo bar baz')
        );
    }

    /**
     * @test
     */
    public function formatOtherWithBaseTitle()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>Cool Web App</title></head><body>foo bar baz</body></html>',
                            $this->htmlFormatter->setBaseTitle('Cool Web App')
                                                ->format('foo bar baz')
        );
    }

    /**
     * @test
     */
    public function forbiddenWithDefaultTemplate()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>403 Forbidden</title><meta name="robots" content="noindex"/></head><body><h1>403 Forbidden</h1><p>You are not allowed to access this resource.</p></body></html>',
                            $this->htmlFormatter->formatForbiddenError()
        );
    }

    /**
     * @test
     */
    public function forbiddenWithDifferentTemplate()
    {
        $this->assertEquals('<html><head><title>403 Forbidden</title><meta author="me"/></head><body><h1>403 Forbidden</h1><p>You are not allowed to access this resource.</p></body></html>',
                            $this->htmlFormatter->setTemplate('<html><head><title>{TITLE}</title><meta author="me"/></head><body>{CONTENT}</body></html>')
                                                ->formatForbiddenError()
        );
    }

    /**
     * @test
     */
    public function notFoundWithDefaultTemplate()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>404 Not Found</title><meta name="robots" content="noindex"/></head><body><h1>404 Not Found</h1><p>The requested resource could not be found.</p></body></html>',
                            $this->htmlFormatter->formatNotFoundError()
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedWithDefaultTemplate()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>405 Method Not Allowed</title><meta name="robots" content="noindex"/></head><body><h1>405 Method Not Allowed</h1><p>The given request method POST is not valid. Please use one of GET, HEAD.</p></body></html>',
                            $this->htmlFormatter->formatMethodNotAllowedError('POST', array('GET', 'HEAD'))
        );
    }

    /**
     * @test
     */
    public function internalServerErrorWithDefaultTemplate()
    {
        $this->assertEquals('<!DOCTYPE html><html><head><title>500 Internal Server Error</title><meta name="robots" content="noindex"/></head><body><h1>500 Internal Server Error</h1><p>Ups!</p></body></html>',
                            $this->htmlFormatter->formatInternalServerError('Ups!')
        );
    }
}
?>