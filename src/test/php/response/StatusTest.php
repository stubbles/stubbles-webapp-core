<?php
declare(strict_types=1);
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

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\Status.
 *
 * @group  response_1
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
        assert($this->status->code(), equals(200));
    }

    /**
     * @test
     */
    public function isNotFixedByDefault()
    {
        assertFalse($this->status->isFixed());
    }

    /**
     * @test
     */
    public function allowsPayloadByDefault()
    {
        assertTrue($this->status->allowsPayload());
    }

    /**
     * @test
     */
    public function lineForCgiSapi()
    {
        assert(
                $this->status->line(HttpVersion::HTTP_1_1, 'cgi'),
                equals('Status: 200 OK')
        );
    }

    /**
     * @test
     */
    public function lineForOtherSapi()
    {
        assert(
                $this->status->line(HttpVersion::HTTP_1_1),
                equals(HttpVersion::HTTP_1_1 . ' 200 OK')
        );
    }

    /**
     * @test
     */
    public function setUnknownStatusCodeWithoutReasonPhraseThrowsIllegalArgumentException()
    {
        expect(function() { $this->status->setCode(909); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function setUnknownStatusCodeWithReasonPhraseIsAccepted()
    {
        assert(
                $this->status->setCode(909, 'Sound Is Awesome')
                        ->line(HttpVersion::HTTP_1_1),
                equals(HttpVersion::HTTP_1_1 . ' 909 Sound Is Awesome')
        );
    }

    /**
     * @test
     */
    public function createdSetsStatusCodeTo201()
    {
        assert(
                $this->status->created('http://example.com/foo')->code(),
                equals(201)
        );
    }

    /**
     * @test
     */
    public function createdAddsLocationHeaderWithGivenUri()
    {
        $this->status->created('http://example.com/foo');
        assert($this->headers['Location'], equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function createdDoesNotAddEtagHeaderByDefault()
    {
        $this->status->created('http://example.com/foo');
        assertFalse(isset($this->headers['ETag']));
    }

    /**
     * @test
     */
    public function createdAddsEtagHeaderWhenGiven()
    {
        $this->status->created('http://example.com/foo', 'someValue');
        assert($this->headers['ETag'], equals('someValue'));
    }

    /**
     * @test
     */
    public function createdFixatesStatusCode()
    {
        assertTrue($this->status->created('http://example.com/foo')->isFixed());
    }

    /**
     * @test
     */
    public function acceptedSetsStatusCodeTo202()
    {
        assert($this->status->accepted()->code(), equals(202));
    }

    /**
     * @test
     */
    public function acceptedFixatesStatusCode()
    {
        assertTrue($this->status->accepted()->isFixed());
    }

    /**
     * @test
     */
    public function noContentSetsStatusCodeTo204()
    {
        assert($this->status->noContent()->code(), equals(204));
    }

    /**
     * @test
     */
    public function noContentAddsContentLengthHeaderWithValue0()
    {
        $this->status->noContent();
        assert($this->headers['Content-Length'], equals('0'));
    }

    /**
     * @test
     */
    public function noContentDisallowsPayload()
    {
        assertFalse($this->status->noContent()->allowsPayload());
    }

    /**
     * @test
     */
    public function noContentFixatesStatusCode()
    {
        assertTrue($this->status->noContent()->isFixed());
    }

    /**
     * @test
     */
    public function resetContentSetsStatusCodeTo205()
    {
        assert($this->status->resetContent()->code(), equals(205));
    }

    /**
     * @test
     */
    public function resetContentAddsContentLengthHeaderWithValue0()
    {
        $this->status->resetContent();
        assert($this->headers['Content-Length'], equals('0'));
    }

    /**
     * @test
     */
    public function resetContentDisallowsPayload()
    {
        assertFalse($this->status->resetContent()->allowsPayload());
    }

    /**
     * @test
     */
    public function resetContentFixatesStatusCode()
    {
        assertTrue($this->status->resetContent()->isFixed());
    }

    /**
     * @test
     */
    public function partialContentSetsStatusCodeTo206()
    {
        assert($this->status->partialContent(0, 10)->code(), equals(206));
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeader()
    {
        $this->status->partialContent(0, 10);
        assert($this->headers['Content-Range'], equals('bytes 0-10/*'));
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeaderWithTotalSizeAndDifferentUnit()
    {
        $this->status->partialContent(0, 10, 25, 'elements');
        assert($this->headers['Content-Range'], equals('elements 0-10/25'));
    }

    /**
     * @test
     */
    public function partialContentFixatesStatusCode()
    {
        assertTrue($this->status->partialContent(0, 10)->isFixed());
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeTo302ByDefault()
    {
        assert(
                $this->status->redirect('http://example.com/foo')->code(),
                equals(302)
        );
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeToGivenStatusCode()
    {
        assert(
                $this->status->redirect('http://example.com/foo', 301)->code(),
                equals(301)
        );
    }

    /**
     * @test
     */
    public function redirectAddsLocationHeaderWithGivenUri()
    {
        $this->status->redirect('http://example.com/foo');
        assert($this->headers['Location'], equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function redirectFixatesStatusCode()
    {
        assertTrue($this->status->redirect('http://example.com/foo')->isFixed());
    }

    /**
     * @test
     */
    public function notModifiedSetsStatusCodeTo304()
    {
        assert($this->status->notModified()->code(), equals(304));
    }

    /**
     * @test
     */
    public function notModifiedFixatesStatusCode()
    {
        assertTrue($this->status->notModified()->isFixed());
    }

    /**
     * @test
     */
    public function badRequestSetsStatusCodeTo400()
    {
        assert($this->status->badRequest()->code(), equals(400));
    }

    /**
     * @test
     */
    public function badRequestFixatesStatusCode()
    {
        assertTrue($this->status->badRequest()->isFixed());
    }

    /**
     * @test
     */
    public function unauthorizedSetsStatusCodeTo401()
    {
        assert(
                $this->status->unauthorized(['Basic realm="RealmName"'])->code(),
                equals(401)
        );
    }

    /**
     * @test
     */
    public function unauthorizedThrowsInvalidArgumentExceptionWhenListOfChallengesIsEmpty()
    {
        expect(function() { $this->status->unauthorized([]); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function unauthorizedAddsWwwAuthenticateHeader()
    {
        $this->status->unauthorized(
                ['MyAuth realm="Yo"', 'Basic realm="RealmName"']
        );
        assert(
                $this->headers['WWW-Authenticate'],
                equals('MyAuth realm="Yo", Basic realm="RealmName"')
        );
    }

    /**
     * @test
     */
    public function unauthorizedFixatesStatusCode()
    {
        assertTrue(
                $this->status->unauthorized(['Basic realm="RealmName"'])
                        ->isFixed()
        );
    }

    /**
     * @test
     */
    public function forbiddenSetsStatusCodeTo403()
    {
        assert($this->status->forbidden()->code(), equals(403));
    }

    /**
     * @test
     */
    public function forbiddenFixatesStatusCode()
    {
        assertTrue($this->status->forbidden()->isFixed());
    }

    /**
     * @test
     */
    public function notFoundSetsStatusCodeTo404()
    {
        assert($this->status->notFound()->code(), equals(404));
    }

    /**
     * @test
     */
    public function notFoundFixatesStatusCode()
    {
        assertTrue($this->status->notFound()->isFixed());
    }

    /**
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405()
    {
        assert(
                $this->status->methodNotAllowed(['GET', 'HEAD'])->code(),
                equals(405)
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedAddsAllowHeader()
    {
        $this->status->methodNotAllowed(['GET', 'HEAD']);
        assert($this->headers['Allow'], equals('GET, HEAD'));
    }

    /**
     * @test
     */
    public function methodNotAllowedFixatesStatusCode()
    {
        assertTrue($this->status->methodNotAllowed(['GET', 'HEAD'])->isFixed());
    }

    /**
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406()
    {
        assert(
                $this->status->notAcceptable(['text/plain', 'application/foo'])
                        ->code(),
                equals(406)
        );
    }

    /**
     * @test
     */
    public function notAcceptableAddsAcceptableHeader()
    {
        $this->status->notAcceptable(['text/plain', 'application/foo']);
        assert(
                $this->headers['X-Acceptable'],
                equals('text/plain, application/foo')
        );
    }

    /**
     * @test
     */
    public function notAcceptableFixatesStatusCode()
    {
        assertTrue(
                $this->status->notAcceptable(['text/plain', 'application/foo'])
                        ->isFixed()
        );
    }

    /**
     * @test
     */
    public function conflictSetsStatusCodeTo409()
    {
        assert($this->status->conflict()->code(), equals(409));
    }

    /**
     * @test
     */
    public function conflictFixatesStatusCode()
    {
        assertTrue($this->status->conflict()->isFixed());
    }

    /**
     * @test
     */
    public function goneSetsStatusCodeTo410()
    {
        assert($this->status->gone()->code(), equals(410));
    }

    /**
     * @test
     */
    public function goneFixatesStatusCode()
    {
        assertTrue($this->status->gone()->isFixed());
    }

    /**
     * @test
     */
    public function lengthRequiredSetsStatusCodeTo411()
    {
        assert($this->status->lengthRequired()->code(), equals(411));
    }

    /**
     * @test
     */
    public function lengthRequiredFixatesStatusCode()
    {
        assertTrue($this->status->lengthRequired()->isFixed());
    }

    /**
     * @test
     */
    public function preconditionFailedSetsStatusCodeTo412()
    {
        assert($this->status->preconditionFailed()->code(), equals(412));
    }

    /**
     * @test
     */
    public function preconditionFailedFixatesStatusCode()
    {
        assertTrue($this->status->preconditionFailed()->isFixed());
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeSetsStatusCodeTo415()
    {
        assert($this->status->unsupportedMediaType()->code(), equals(415));
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeFixatesStatusCode()
    {
        assertTrue($this->status->unsupportedMediaType()->isFixed());
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableSetsStatusCodeTo416()
    {
        assert($this->status->rangeNotSatisfiable(22)->code(), equals(416));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeader()
    {
        $this->status->rangeNotSatisfiable(22);
        assert($this->headers['Content-Range'], equals('bytes */22'));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeaderWithDifferentUnit()
    {
        $this->status->rangeNotSatisfiable(22, 'elements');
        assert($this->headers['Content-Range'], equals('elements */22'));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableFixatesStatusCode()
    {
        assertTrue($this->status->rangeNotSatisfiable(22)->isFixed());
    }

    /**
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500()
    {
        assert($this->status->internalServerError()->code(), equals(500));
    }

    /**
     * @test
     */
    public function internalServerErrorFixatesStatusCode()
    {
        assertTrue($this->status->internalServerError()->isFixed());
    }

    /**
     * @test
     */
    public function notImplementedSetsStatusCodeTo501()
    {
        assert($this->status->notImplemented()->code(), equals(501));
    }

    /**
     * @test
     */
    public function notImplementedFixatesStatusCode()
    {
        assertTrue($this->status->notImplemented()->isFixed());
    }

    /**
     * @test
     */
    public function serviceUnavailableSetsStatusCodeTo503()
    {
        assert($this->status->serviceUnavailable()->code(), equals(503));
    }

    /**
     * @test
     */
    public function serviceUnavailableFixatesStatusCode()
    {
        assertTrue($this->status->serviceUnavailable()->isFixed());
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505()
    {
        assert($this->status->httpVersionNotSupported()->code(), equals(505));
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedFixatesStatusCode()
    {
        assertTrue($this->status->httpVersionNotSupported()->isFixed());
    }
}
