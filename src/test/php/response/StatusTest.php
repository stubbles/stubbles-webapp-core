<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\HttpVersion;
/**
 * Tests for stubbles\webapp\response\Status.
 *
 * @group  response
 * @since  5.1.0
 */
class StatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\response\Status
     */
    private $status;
    /**
     * @type  stubbles\webapp\response\Headers
     */
    private $headers;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->headers = new Headers();
        $this->status  = new Status($this->headers);
    }

    /**
     * @test
     */
    public function statusCodeIs200ByDefault()
    {
        $this->assertEquals(200, $this->status->code());
    }

    /**
     * @test
     */
    public function isNotFixedByDefault()
    {
        $this->assertFalse($this->status->isFixed());
    }

    /**
     * @test
     */
    public function allowsPayloadByDefault()
    {
        $this->assertTrue($this->status->allowsPayload());
    }

    /**
     * @test
     */
    public function lineForCgiSapi()
    {
        $this->assertEquals(
                'Status: 200 OK',
                $this->status->line(HttpVersion::HTTP_1_1, 'cgi')
        );
    }

    /**
     * @test
     */
    public function lineForOtherSapi()
    {
        $this->assertEquals(
                HttpVersion::HTTP_1_1 . ' 200 OK',
                $this->status->line(HttpVersion::HTTP_1_1)
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function setUnknownStatusCodeWithoutReasonPhraseThrowsIllegalArgumentException()
    {
        $this->status->setCode(909);
    }

    /**
     * @test
     */
    public function setUnknownStatusCodeWithReasonPhraseIsAccepted()
    {
        $this->assertEquals(
                HttpVersion::HTTP_1_1 . ' 909 Sound Is Awesome',
                $this->status->setCode(909, 'Sound Is Awesome')
                             ->line(HttpVersion::HTTP_1_1)
        );
    }

    /**
     * @test
     */
    public function createdSetsStatusCodeTo201()
    {
        $this->assertEquals(
                201,
                $this->status->created('http://example.com/foo')->code()
        );
    }

    /**
     * @test
     */
    public function createdAddsLocationHeaderWithGivenUri()
    {
        $this->status->created('http://example.com/foo');
        $this->assertEquals('http://example.com/foo', $this->headers['Location']);
    }

    /**
     * @test
     */
    public function createdDoesNotAddEtagHeaderByDefault()
    {
        $this->status->created('http://example.com/foo');
        $this->assertFalse(isset($this->headers['ETag']));
    }

    /**
     * @test
     */
    public function createdAddsEtagHeaderWhenGiven()
    {
        $this->status->created('http://example.com/foo', 'someValue');
        $this->assertEquals('someValue', $this->headers['ETag']);
    }

    /**
     * @test
     */
    public function createdFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->created('http://example.com/foo')->isFixed()
        );
    }

    /**
     * @test
     */
    public function acceptedSetsStatusCodeTo202()
    {
        $this->assertEquals(
                202,
                $this->status->accepted()->code()
        );
    }

    /**
     * @test
     */
    public function acceptedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->accepted()->isFixed()
        );
    }

    /**
     * @test
     */
    public function noContentSetsStatusCodeTo204()
    {
        $this->assertEquals(
                204,
                $this->status->noContent()->code()
        );
    }

    /**
     * @test
     */
    public function noContentAddsContentLengthHeaderWithValue0()
    {
        $this->status->noContent();
        $this->assertEquals('0', $this->headers['Content-Length']);
    }

    /**
     * @test
     */
    public function noContentDisallowsPayload()
    {
        $this->assertFalse(
                $this->status->noContent()->allowsPayload()
        );
    }

    /**
     * @test
     */
    public function noContentFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->noContent()->isFixed()
        );
    }

    /**
     * @test
     */
    public function resetContentSetsStatusCodeTo205()
    {
        $this->assertEquals(
                205,
                $this->status->resetContent()->code()
        );
    }

    /**
     * @test
     */
    public function resetContentAddsContentLengthHeaderWithValue0()
    {
        $this->status->resetContent();
        $this->assertEquals('0', $this->headers['Content-Length']);
    }

    /**
     * @test
     */
    public function resetContentDisallowsPayload()
    {
        $this->assertFalse(
                $this->status->resetContent()->allowsPayload()
        );
    }

    /**
     * @test
     */
    public function resetContentFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->resetContent()->isFixed()
        );
    }

    /**
     * @test
     */
    public function partialContentSetsStatusCodeTo206()
    {
        $this->assertEquals(
                206,
                $this->status->partialContent(0, 10)->code()
        );
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeader()
    {
        $this->status->partialContent(0, 10);
        $this->assertEquals(
                'bytes 0-10/*',
                $this->headers['Content-Range']
        );
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeaderWithTotalSizeAndDifferentUnit()
    {
        $this->status->partialContent(0, 10, 25, 'elements');
        $this->assertEquals(
                'elements 0-10/25',
                $this->headers['Content-Range']
        );
    }

    /**
     * @test
     */
    public function partialContentFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->partialContent(0, 10)->isFixed()
        );
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeTo302ByDefault()
    {
        $this->assertEquals(
                302,
                $this->status->redirect('http://example.com/foo')->code()
        );
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeToGivenStatusCode()
    {
        $this->assertEquals(
                301,
                $this->status->redirect('http://example.com/foo', 301)->code()
        );
    }

    /**
     * @test
     */
    public function redirectAddsLocationHeaderWithGivenUri()
    {
        $this->status->redirect('http://example.com/foo');
        $this->assertEquals('http://example.com/foo', $this->headers['Location']);
    }

    /**
     * @test
     */
    public function redirectFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->redirect('http://example.com/foo')->isFixed()
        );
    }

    /**
     * @test
     */
    public function notModifiedSetsStatusCodeTo304()
    {
        $this->assertEquals(
                304,
                $this->status->notModified()->code()
        );
    }

    /**
     * @test
     */
    public function notModifiedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->notModified()->isFixed()
        );
    }

    /**
     * @test
     */
    public function badRequestSetsStatusCodeTo400()
    {
        $this->assertEquals(
                400,
                $this->status->badRequest()->code()
        );
    }

    /**
     * @test
     */
    public function badRequestFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->badRequest()->isFixed()
        );
    }

    /**
     * @test
     */
    public function unauthorizedSetsStatusCodeTo401()
    {
        $this->assertEquals(
                401,
                $this->status->unauthorized(['Basic realm="RealmName"'])->code()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function unauthorizedThrowsInvalidArgumentExceptionWhenListOfChallengesIsEmpty()
    {
        $this->status->unauthorized([]);
    }

    /**
     * @test
     */
    public function unauthorizedAddsWwwAuthenticateHeader()
    {
        $this->status->unauthorized(['MyAuth realm="Yo"', 'Basic realm="RealmName"']);
        $this->assertEquals(
                'MyAuth realm="Yo", Basic realm="RealmName"',
                $this->headers['WWW-Authenticate']
        );
    }

    /**
     * @test
     */
    public function unauthorizedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->unauthorized(['Basic realm="RealmName"'])->isFixed()
        );
    }

    /**
     * @test
     */
    public function forbiddenSetsStatusCodeTo403()
    {
        $this->assertEquals(
                403,
                $this->status->forbidden()->code()
        );
    }

    /**
     * @test
     */
    public function forbiddenFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->forbidden()->isFixed()
        );
    }

    /**
     * @test
     */
    public function notFoundSetsStatusCodeTo404()
    {
        $this->assertEquals(
                404,
                $this->status->notFound()->code()
        );
    }

    /**
     * @test
     */
    public function notFoundFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->notFound()->isFixed()
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405()
    {
        $this->assertEquals(
                405,
                $this->status->methodNotAllowed(['GET', 'HEAD'])->code()
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedAddsAllowHeader()
    {
        $this->status->methodNotAllowed(['GET', 'HEAD']);
        $this->assertEquals(
                'GET, HEAD',
                $this->headers['Allow']
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->methodNotAllowed(['GET', 'HEAD'])->isFixed()
        );
    }

    /**
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406()
    {
        $this->assertEquals(
                406,
                $this->status->notAcceptable(['text/plain', 'application/foo'])->code()
        );
    }

    /**
     * @test
     */
    public function notAcceptableAddsAcceptableHeader()
    {
        $this->status->notAcceptable(['text/plain', 'application/foo']);
        $this->assertEquals(
                'text/plain, application/foo',
                $this->headers['X-Acceptable']
        );
    }

    /**
     * @test
     */
    public function notAcceptableFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->notAcceptable(['text/plain', 'application/foo'])->isFixed()
        );
    }

    /**
     * @test
     */
    public function conflictSetsStatusCodeTo409()
    {
        $this->assertEquals(
                409,
                $this->status->conflict()->code()
        );
    }

    /**
     * @test
     */
    public function conflictFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->conflict()->isFixed()
        );
    }

    /**
     * @test
     */
    public function goneSetsStatusCodeTo410()
    {
        $this->assertEquals(
                410,
                $this->status->gone()->code()
        );
    }

    /**
     * @test
     */
    public function goneFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->gone()->isFixed()
        );
    }

    /**
     * @test
     */
    public function lengthRequiredSetsStatusCodeTo411()
    {
        $this->assertEquals(
                411,
                $this->status->lengthRequired()->code()
        );
    }

    /**
     * @test
     */
    public function lengthRequiredFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->lengthRequired()->isFixed()
        );
    }

    /**
     * @test
     */
    public function preconditionFailedSetsStatusCodeTo412()
    {
        $this->assertEquals(
                412,
                $this->status->preconditionFailed()->code()
        );
    }

    /**
     * @test
     */
    public function preconditionFailedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->preconditionFailed()->isFixed()
        );
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeSetsStatusCodeTo415()
    {
        $this->assertEquals(
                415,
                $this->status->unsupportedMediaType()->code()
        );
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->unsupportedMediaType()->isFixed()
        );
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableSetsStatusCodeTo416()
    {
        $this->assertEquals(
                416,
                $this->status->rangeNotSatisfiable(22)->code()
        );
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeader()
    {
        $this->status->rangeNotSatisfiable(22);
        $this->assertEquals(
                'bytes */22',
                $this->headers['Content-Range']
        );
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeaderWithDifferentUnit()
    {
        $this->status->rangeNotSatisfiable(22, 'elements');
        $this->assertEquals(
                'elements */22',
                $this->headers['Content-Range']
        );
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->rangeNotSatisfiable(22)->isFixed()
        );
    }

    /**
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500()
    {
        $this->assertEquals(
                500,
                $this->status->internalServerError()->code()
        );
    }

    /**
     * @test
     */
    public function internalServerErrorFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->internalServerError()->isFixed()
        );
    }

    /**
     * @test
     */
    public function notImplementedSetsStatusCodeTo501()
    {
        $this->assertEquals(
                501,
                $this->status->notImplemented()->code()
        );
    }

    /**
     * @test
     */
    public function notImplementedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->notImplemented()->isFixed()
        );
    }

    /**
     * @test
     */
    public function serviceUnavailableSetsStatusCodeTo503()
    {
        $this->assertEquals(
                503,
                $this->status->serviceUnavailable()->code()
        );
    }

    /**
     * @test
     */
    public function serviceUnavailableFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->serviceUnavailable()->isFixed()
        );
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505()
    {
        $this->assertEquals(
                505,
                $this->status->httpVersionNotSupported()->code()
        );
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedFixatesStatusCode()
    {
        $this->assertTrue(
                $this->status->httpVersionNotSupported()->isFixed()
        );
    }
}
