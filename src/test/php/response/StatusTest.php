<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\HttpVersion;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals
};
/**
 * Tests for stubbles\webapp\response\Status.
 *
 * @group  response_1
 * @since  5.1.0
 */
class StatusTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  \stubbles\webapp\response\Status
     */
    private $status;
    /**
     * @var  stubbles\webapp\response\Headers
     */
    private $headers;

    protected function setUp(): void
    {
        $this->headers = new Headers();
        $this->status  = new Status($this->headers);
    }

    /**
     * @test
     */
    public function statusCodeIs200ByDefault(): void
    {
        assertThat($this->status->code(), equals(200));
    }

    /**
     * @test
     */
    public function isNotFixedByDefault(): void
    {
        assertFalse($this->status->isFixed());
    }

    /**
     * @test
     */
    public function allowsPayloadByDefault(): void
    {
        assertTrue($this->status->allowsPayload());
    }

    /**
     * @test
     */
    public function lineForCgiSapi(): void
    {
        assertThat(
                $this->status->line(HttpVersion::HTTP_1_1, 'cgi'),
                equals('Status: 200 OK')
        );
    }

    /**
     * @test
     */
    public function lineForOtherSapi(): void
    {
        assertThat(
                $this->status->line(HttpVersion::HTTP_1_1),
                equals(HttpVersion::HTTP_1_1 . ' 200 OK')
        );
    }

    /**
     * @test
     */
    public function setUnknownStatusCodeWithoutReasonPhraseThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->status->setCode(909); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function setUnknownStatusCodeWithReasonPhraseIsAccepted(): void
    {
        assertThat(
                $this->status->setCode(909, 'Sound Is Awesome')
                        ->line(HttpVersion::HTTP_1_1),
                equals(HttpVersion::HTTP_1_1 . ' 909 Sound Is Awesome')
        );
    }

    /**
     * @test
     */
    public function createdSetsStatusCodeTo201(): void
    {
        assertThat(
                $this->status->created('http://example.com/foo')->code(),
                equals(201)
        );
    }

    /**
     * @test
     */
    public function createdAddsLocationHeaderWithGivenUri(): void
    {
        $this->status->created('http://example.com/foo');
        assertThat($this->headers['Location'], equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function createdDoesNotAddEtagHeaderByDefault(): void
    {
        $this->status->created('http://example.com/foo');
        assertFalse(isset($this->headers['ETag']));
    }

    /**
     * @test
     */
    public function createdAddsEtagHeaderWhenGiven(): void
    {
        $this->status->created('http://example.com/foo', 'someValue');
        assertThat($this->headers['ETag'], equals('someValue'));
    }

    /**
     * @test
     */
    public function createdFixatesStatusCode(): void
    {
        assertTrue($this->status->created('http://example.com/foo')->isFixed());
    }

    /**
     * @test
     */
    public function acceptedSetsStatusCodeTo202(): void
    {
        assertThat($this->status->accepted()->code(), equals(202));
    }

    /**
     * @test
     */
    public function acceptedFixatesStatusCode(): void
    {
        assertTrue($this->status->accepted()->isFixed());
    }

    /**
     * @test
     */
    public function noContentSetsStatusCodeTo204(): void
    {
        assertThat($this->status->noContent()->code(), equals(204));
    }

    /**
     * @test
     */
    public function noContentAddsContentLengthHeaderWithValue0(): void
    {
        $this->status->noContent();
        assertThat($this->headers['Content-Length'], equals('0'));
    }

    /**
     * @test
     */
    public function noContentDisallowsPayload(): void
    {
        assertFalse($this->status->noContent()->allowsPayload());
    }

    /**
     * @test
     */
    public function noContentFixatesStatusCode(): void
    {
        assertTrue($this->status->noContent()->isFixed());
    }

    /**
     * @test
     */
    public function resetContentSetsStatusCodeTo205(): void
    {
        assertThat($this->status->resetContent()->code(), equals(205));
    }

    /**
     * @test
     */
    public function resetContentAddsContentLengthHeaderWithValue0(): void
    {
        $this->status->resetContent();
        assertThat($this->headers['Content-Length'], equals('0'));
    }

    /**
     * @test
     */
    public function resetContentDisallowsPayload(): void
    {
        assertFalse($this->status->resetContent()->allowsPayload());
    }

    /**
     * @test
     */
    public function resetContentFixatesStatusCode(): void
    {
        assertTrue($this->status->resetContent()->isFixed());
    }

    /**
     * @test
     */
    public function partialContentSetsStatusCodeTo206(): void
    {
        assertThat($this->status->partialContent(0, 10)->code(), equals(206));
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeader(): void
    {
        $this->status->partialContent(0, 10);
        assertThat($this->headers['Content-Range'], equals('bytes 0-10/*'));
    }

    /**
     * @test
     */
    public function partialContentAddsContentRangeHeaderWithTotalSizeAndDifferentUnit(): void
    {
        $this->status->partialContent(0, 10, 25, 'elements');
        assertThat($this->headers['Content-Range'], equals('elements 0-10/25'));
    }

    /**
     * @test
     */
    public function partialContentFixatesStatusCode(): void
    {
        assertTrue($this->status->partialContent(0, 10)->isFixed());
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeTo302ByDefault(): void
    {
        assertThat(
                $this->status->redirect('http://example.com/foo')->code(),
                equals(302)
        );
    }

    /**
     * @test
     */
    public function redirectSetsStatusCodeToGivenStatusCode(): void
    {
        assertThat(
                $this->status->redirect('http://example.com/foo', 301)->code(),
                equals(301)
        );
    }

    /**
     * @test
     */
    public function redirectAddsLocationHeaderWithGivenUri(): void
    {
        $this->status->redirect('http://example.com/foo');
        assertThat($this->headers['Location'], equals('http://example.com/foo'));
    }

    /**
     * @test
     */
    public function redirectFixatesStatusCode(): void
    {
        assertTrue($this->status->redirect('http://example.com/foo')->isFixed());
    }

    /**
     * @test
     */
    public function notModifiedSetsStatusCodeTo304(): void
    {
        assertThat($this->status->notModified()->code(), equals(304));
    }

    /**
     * @test
     */
    public function notModifiedFixatesStatusCode(): void
    {
        assertTrue($this->status->notModified()->isFixed());
    }

    /**
     * @test
     */
    public function badRequestSetsStatusCodeTo400(): void
    {
        assertThat($this->status->badRequest()->code(), equals(400));
    }

    /**
     * @test
     */
    public function badRequestFixatesStatusCode(): void
    {
        assertTrue($this->status->badRequest()->isFixed());
    }

    /**
     * @test
     */
    public function unauthorizedSetsStatusCodeTo401(): void
    {
        assertThat(
                $this->status->unauthorized(['Basic realm="RealmName"'])->code(),
                equals(401)
        );
    }

    /**
     * @test
     */
    public function unauthorizedThrowsInvalidArgumentExceptionWhenListOfChallengesIsEmpty(): void
    {
        expect(function() { $this->status->unauthorized([]); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function unauthorizedAddsWwwAuthenticateHeader(): void
    {
        $this->status->unauthorized(
                ['MyAuth realm="Yo"', 'Basic realm="RealmName"']
        );
        assertThat(
                $this->headers['WWW-Authenticate'],
                equals('MyAuth realm="Yo", Basic realm="RealmName"')
        );
    }

    /**
     * @test
     */
    public function unauthorizedFixatesStatusCode(): void
    {
        assertTrue(
                $this->status->unauthorized(['Basic realm="RealmName"'])
                        ->isFixed()
        );
    }

    /**
     * @test
     */
    public function forbiddenSetsStatusCodeTo403(): void
    {
        assertThat($this->status->forbidden()->code(), equals(403));
    }

    /**
     * @test
     */
    public function forbiddenFixatesStatusCode(): void
    {
        assertTrue($this->status->forbidden()->isFixed());
    }

    /**
     * @test
     */
    public function notFoundSetsStatusCodeTo404(): void
    {
        assertThat($this->status->notFound()->code(), equals(404));
    }

    /**
     * @test
     */
    public function notFoundFixatesStatusCode(): void
    {
        assertTrue($this->status->notFound()->isFixed());
    }

    /**
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405(): void
    {
        assertThat(
                $this->status->methodNotAllowed(['GET', 'HEAD'])->code(),
                equals(405)
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedAddsAllowHeader(): void
    {
        $this->status->methodNotAllowed(['GET', 'HEAD']);
        assertThat($this->headers['Allow'], equals('GET, HEAD'));
    }

    /**
     * @test
     */
    public function methodNotAllowedFixatesStatusCode(): void
    {
        assertTrue($this->status->methodNotAllowed(['GET', 'HEAD'])->isFixed());
    }

    /**
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406(): void
    {
        assertThat(
                $this->status->notAcceptable(['text/plain', 'application/foo'])
                        ->code(),
                equals(406)
        );
    }

    /**
     * @test
     */
    public function notAcceptableAddsAcceptableHeader(): void
    {
        $this->status->notAcceptable(['text/plain', 'application/foo']);
        assertThat(
                $this->headers['X-Acceptable'],
                equals('text/plain, application/foo')
        );
    }

    /**
     * @test
     */
    public function notAcceptableFixatesStatusCode(): void
    {
        assertTrue(
                $this->status->notAcceptable(['text/plain', 'application/foo'])
                        ->isFixed()
        );
    }

    /**
     * @test
     */
    public function conflictSetsStatusCodeTo409(): void
    {
        assertThat($this->status->conflict()->code(), equals(409));
    }

    /**
     * @test
     */
    public function conflictFixatesStatusCode(): void
    {
        assertTrue($this->status->conflict()->isFixed());
    }

    /**
     * @test
     */
    public function goneSetsStatusCodeTo410(): void
    {
        assertThat($this->status->gone()->code(), equals(410));
    }

    /**
     * @test
     */
    public function goneFixatesStatusCode(): void
    {
        assertTrue($this->status->gone()->isFixed());
    }

    /**
     * @test
     */
    public function lengthRequiredSetsStatusCodeTo411(): void
    {
        assertThat($this->status->lengthRequired()->code(), equals(411));
    }

    /**
     * @test
     */
    public function lengthRequiredFixatesStatusCode(): void
    {
        assertTrue($this->status->lengthRequired()->isFixed());
    }

    /**
     * @test
     */
    public function preconditionFailedSetsStatusCodeTo412(): void
    {
        assertThat($this->status->preconditionFailed()->code(), equals(412));
    }

    /**
     * @test
     */
    public function preconditionFailedFixatesStatusCode(): void
    {
        assertTrue($this->status->preconditionFailed()->isFixed());
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeSetsStatusCodeTo415(): void
    {
        assertThat($this->status->unsupportedMediaType()->code(), equals(415));
    }

    /**
     * @test
     */
    public function unsupportedMediaTypeFixatesStatusCode(): void
    {
        assertTrue($this->status->unsupportedMediaType()->isFixed());
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableSetsStatusCodeTo416(): void
    {
        assertThat($this->status->rangeNotSatisfiable(22)->code(), equals(416));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeader(): void
    {
        $this->status->rangeNotSatisfiable(22);
        assertThat($this->headers['Content-Range'], equals('bytes */22'));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableAddsContentRangeHeaderWithDifferentUnit(): void
    {
        $this->status->rangeNotSatisfiable(22, 'elements');
        assertThat($this->headers['Content-Range'], equals('elements */22'));
    }

    /**
     * @test
     */
    public function rangeNotSatisfiableFixatesStatusCode(): void
    {
        assertTrue($this->status->rangeNotSatisfiable(22)->isFixed());
    }

    /**
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500(): void
    {
        assertThat($this->status->internalServerError()->code(), equals(500));
    }

    /**
     * @test
     */
    public function internalServerErrorFixatesStatusCode(): void
    {
        assertTrue($this->status->internalServerError()->isFixed());
    }

    /**
     * @test
     */
    public function notImplementedSetsStatusCodeTo501(): void
    {
        assertThat($this->status->notImplemented()->code(), equals(501));
    }

    /**
     * @test
     */
    public function notImplementedFixatesStatusCode(): void
    {
        assertTrue($this->status->notImplemented()->isFixed());
    }

    /**
     * @test
     */
    public function serviceUnavailableSetsStatusCodeTo503(): void
    {
        assertThat($this->status->serviceUnavailable()->code(), equals(503));
    }

    /**
     * @test
     */
    public function serviceUnavailableFixatesStatusCode(): void
    {
        assertTrue($this->status->serviceUnavailable()->isFixed());
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505(): void
    {
        assertThat($this->status->httpVersionNotSupported()->code(), equals(505));
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedFixatesStatusCode(): void
    {
        assertTrue($this->status->httpVersionNotSupported()->isFixed());
    }
}
