<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * @since  5.1.0
 */
#[Group('response')]
class StatusTest extends TestCase
{
    private Status $status;
    private Headers $headers;

    protected function setUp(): void
    {
        $this->headers = new Headers();
        $this->status  = new Status($this->headers);
    }

    #[Test]
    public function statusCodeIs200ByDefault(): void
    {
        assertThat($this->status->code(), equals(200));
    }

    #[Test]
    public function isNotFixedByDefault(): void
    {
        assertFalse($this->status->isFixed());
    }

    #[Test]
    public function allowsPayloadByDefault(): void
    {
        assertTrue($this->status->allowsPayload());
    }

    #[Test]
    public function lineForCgiSapi(): void
    {
        assertThat(
            $this->status->line(HttpVersion::HTTP_1_1, 'cgi'),
            equals('Status: 200 OK')
        );
    }

    #[Test]
    public function lineForOtherSapi(): void
    {
        assertThat(
            $this->status->line(HttpVersion::HTTP_1_1),
            equals(HttpVersion::HTTP_1_1 . ' 200 OK')
        );
    }

    #[Test]
    public function setUnknownStatusCodeWithoutReasonPhraseThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->status->setCode(909); })
            ->throws(InvalidArgumentException::class);
    }

    #[Test]
    public function setUnknownStatusCodeWithReasonPhraseIsAccepted(): void
    {
        assertThat(
            $this->status->setCode(909, 'Sound Is Awesome')
                ->line(HttpVersion::HTTP_1_1),
            equals(HttpVersion::HTTP_1_1 . ' 909 Sound Is Awesome')
        );
    }

    #[Test]
    public function createdSetsStatusCodeTo201(): void
    {
        assertThat(
            $this->status->created('http://example.com/foo')->code(),
            equals(201)
        );
    }

    #[Test]
    public function createdAddsLocationHeaderWithGivenUri(): void
    {
        $this->status->created('http://example.com/foo');
        assertThat($this->headers['Location'], equals('http://example.com/foo'));
    }

    #[Test]
    public function createdDoesNotAddEtagHeaderByDefault(): void
    {
        $this->status->created('http://example.com/foo');
        assertFalse(isset($this->headers['ETag']));
    }

    #[Test]
    public function createdAddsEtagHeaderWhenGiven(): void
    {
        $this->status->created('http://example.com/foo', 'someValue');
        assertThat($this->headers['ETag'], equals('someValue'));
    }

    #[Test]
    public function createdFixatesStatusCode(): void
    {
        assertTrue($this->status->created('http://example.com/foo')->isFixed());
    }

    #[Test]
    public function acceptedSetsStatusCodeTo202(): void
    {
        assertThat($this->status->accepted()->code(), equals(202));
    }

    #[Test]
    public function acceptedFixatesStatusCode(): void
    {
        assertTrue($this->status->accepted()->isFixed());
    }

    #[Test]
    public function noContentSetsStatusCodeTo204(): void
    {
        assertThat($this->status->noContent()->code(), equals(204));
    }

    #[Test]
    public function noContentAddsContentLengthHeaderWithValue0(): void
    {
        $this->status->noContent();
        assertThat($this->headers['Content-Length'], equals('0'));
    }

    #[Test]
    public function noContentDisallowsPayload(): void
    {
        assertFalse($this->status->noContent()->allowsPayload());
    }

    #[Test]
    public function noContentFixatesStatusCode(): void
    {
        assertTrue($this->status->noContent()->isFixed());
    }

    #[Test]
    public function resetContentSetsStatusCodeTo205(): void
    {
        assertThat($this->status->resetContent()->code(), equals(205));
    }

    #[Test]
    public function resetContentAddsContentLengthHeaderWithValue0(): void
    {
        $this->status->resetContent();
        assertThat($this->headers['Content-Length'], equals('0'));
    }

    #[Test]
    public function resetContentDisallowsPayload(): void
    {
        assertFalse($this->status->resetContent()->allowsPayload());
    }

    #[Test]
    public function resetContentFixatesStatusCode(): void
    {
        assertTrue($this->status->resetContent()->isFixed());
    }

    #[Test]
    public function partialContentSetsStatusCodeTo206(): void
    {
        assertThat($this->status->partialContent(0, 10)->code(), equals(206));
    }

    #[Test]
    public function partialContentAddsContentRangeHeader(): void
    {
        $this->status->partialContent(0, 10);
        assertThat($this->headers['Content-Range'], equals('bytes 0-10/*'));
    }

    #[Test]
    public function partialContentAddsContentRangeHeaderWithTotalSizeAndDifferentUnit(): void
    {
        $this->status->partialContent(0, 10, 25, 'elements');
        assertThat($this->headers['Content-Range'], equals('elements 0-10/25'));
    }

    #[Test]
    public function partialContentFixatesStatusCode(): void
    {
        assertTrue($this->status->partialContent(0, 10)->isFixed());
    }

    #[Test]
    public function redirectSetsStatusCodeTo302ByDefault(): void
    {
        assertThat(
            $this->status->redirect('http://example.com/foo')->code(),
            equals(302)
        );
    }

    #[Test]
    public function redirectSetsStatusCodeToGivenStatusCode(): void
    {
        assertThat(
            $this->status->redirect('http://example.com/foo', 301)->code(),
            equals(301)
        );
    }

    #[Test]
    public function redirectAddsLocationHeaderWithGivenUri(): void
    {
        $this->status->redirect('http://example.com/foo');
        assertThat($this->headers['Location'], equals('http://example.com/foo'));
    }

    #[Test]
    public function redirectFixatesStatusCode(): void
    {
        assertTrue($this->status->redirect('http://example.com/foo')->isFixed());
    }

    #[Test]
    public function notModifiedSetsStatusCodeTo304(): void
    {
        assertThat($this->status->notModified()->code(), equals(304));
    }

    #[Test]
    public function notModifiedFixatesStatusCode(): void
    {
        assertTrue($this->status->notModified()->isFixed());
    }

    #[Test]
    public function badRequestSetsStatusCodeTo400(): void
    {
        assertThat($this->status->badRequest()->code(), equals(400));
    }

    #[Test]
    public function badRequestFixatesStatusCode(): void
    {
        assertTrue($this->status->badRequest()->isFixed());
    }

    #[Test]
    public function unauthorizedSetsStatusCodeTo401(): void
    {
        assertThat(
            $this->status->unauthorized(['Basic realm="RealmName"'])->code(),
            equals(401)
        );
    }

    #[Test]
    public function unauthorizedThrowsInvalidArgumentExceptionWhenListOfChallengesIsEmpty(): void
    {
        expect(function() { $this->status->unauthorized([]); })
            ->throws(InvalidArgumentException::class);
    }

    #[Test]
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

    #[Test]
    public function unauthorizedFixatesStatusCode(): void
    {
        assertTrue(
            $this->status->unauthorized(['Basic realm="RealmName"'])->isFixed()
        );
    }

    #[Test]
    public function forbiddenSetsStatusCodeTo403(): void
    {
        assertThat($this->status->forbidden()->code(), equals(403));
    }

    #[Test]
    public function forbiddenFixatesStatusCode(): void
    {
        assertTrue($this->status->forbidden()->isFixed());
    }

    #[Test]
    public function notFoundSetsStatusCodeTo404(): void
    {
        assertThat($this->status->notFound()->code(), equals(404));
    }

    #[Test]
    public function notFoundFixatesStatusCode(): void
    {
        assertTrue($this->status->notFound()->isFixed());
    }

    #[Test]
    public function methodNotAllowedSetsStatusCodeTo405(): void
    {
        assertThat(
            $this->status->methodNotAllowed(['GET', 'HEAD'])->code(),
            equals(405)
        );
    }

    #[Test]
    public function methodNotAllowedAddsAllowHeader(): void
    {
        $this->status->methodNotAllowed(['GET', 'HEAD']);
        assertThat($this->headers['Allow'], equals('GET, HEAD'));
    }

    #[Test]
    public function methodNotAllowedFixatesStatusCode(): void
    {
        assertTrue($this->status->methodNotAllowed(['GET', 'HEAD'])->isFixed());
    }

    #[Test]
    public function notAcceptableSetsStatusCodeTo406(): void
    {
        assertThat(
            $this->status->notAcceptable(['text/plain', 'application/foo'])->code(),
            equals(406)
        );
    }

    #[Test]
    public function notAcceptableAddsAcceptableHeader(): void
    {
        $this->status->notAcceptable(['text/plain', 'application/foo']);
        assertThat(
            $this->headers['X-Acceptable'],
            equals('text/plain, application/foo')
        );
    }

    #[Test]
    public function notAcceptableFixatesStatusCode(): void
    {
        assertTrue(
            $this->status->notAcceptable(['text/plain', 'application/foo'])->isFixed()
        );
    }

    #[Test]
    public function conflictSetsStatusCodeTo409(): void
    {
        assertThat($this->status->conflict()->code(), equals(409));
    }

    #[Test]
    public function conflictFixatesStatusCode(): void
    {
        assertTrue($this->status->conflict()->isFixed());
    }

    #[Test]
    public function goneSetsStatusCodeTo410(): void
    {
        assertThat($this->status->gone()->code(), equals(410));
    }

    #[Test]
    public function goneFixatesStatusCode(): void
    {
        assertTrue($this->status->gone()->isFixed());
    }

    #[Test]
    public function lengthRequiredSetsStatusCodeTo411(): void
    {
        assertThat($this->status->lengthRequired()->code(), equals(411));
    }

    #[Test]
    public function lengthRequiredFixatesStatusCode(): void
    {
        assertTrue($this->status->lengthRequired()->isFixed());
    }

    #[Test]
    public function preconditionFailedSetsStatusCodeTo412(): void
    {
        assertThat($this->status->preconditionFailed()->code(), equals(412));
    }

    #[Test]
    public function preconditionFailedFixatesStatusCode(): void
    {
        assertTrue($this->status->preconditionFailed()->isFixed());
    }

    #[Test]
    public function unsupportedMediaTypeSetsStatusCodeTo415(): void
    {
        assertThat($this->status->unsupportedMediaType()->code(), equals(415));
    }

    #[Test]
    public function unsupportedMediaTypeFixatesStatusCode(): void
    {
        assertTrue($this->status->unsupportedMediaType()->isFixed());
    }

    #[Test]
    public function rangeNotSatisfiableSetsStatusCodeTo416(): void
    {
        assertThat($this->status->rangeNotSatisfiable(22)->code(), equals(416));
    }

    #[Test]
    public function rangeNotSatisfiableAddsContentRangeHeader(): void
    {
        $this->status->rangeNotSatisfiable(22);
        assertThat($this->headers['Content-Range'], equals('bytes */22'));
    }

    #[Test]
    public function rangeNotSatisfiableAddsContentRangeHeaderWithDifferentUnit(): void
    {
        $this->status->rangeNotSatisfiable(22, 'elements');
        assertThat($this->headers['Content-Range'], equals('elements */22'));
    }

    #[Test]
    public function rangeNotSatisfiableFixatesStatusCode(): void
    {
        assertTrue($this->status->rangeNotSatisfiable(22)->isFixed());
    }

    #[Test]
    public function internalServerErrorSetsStatusCodeTo500(): void
    {
        assertThat($this->status->internalServerError()->code(), equals(500));
    }

    #[Test]
    public function internalServerErrorFixatesStatusCode(): void
    {
        assertTrue($this->status->internalServerError()->isFixed());
    }

    #[Test]
    public function notImplementedSetsStatusCodeTo501(): void
    {
        assertThat($this->status->notImplemented()->code(), equals(501));
    }

    #[Test]
    public function notImplementedFixatesStatusCode(): void
    {
        assertTrue($this->status->notImplemented()->isFixed());
    }

    #[Test]
    public function serviceUnavailableSetsStatusCodeTo503(): void
    {
        assertThat($this->status->serviceUnavailable()->code(), equals(503));
    }

    #[Test]
    public function serviceUnavailableFixatesStatusCode(): void
    {
        assertTrue($this->status->serviceUnavailable()->isFixed());
    }

    #[Test]
    public function httpVersionNotSupportedSetsStatusCodeTo505(): void
    {
        assertThat($this->status->httpVersionNotSupported()->code(), equals(505));
    }

    #[Test]
    public function httpVersionNotSupportedFixatesStatusCode(): void
    {
        assertTrue($this->status->httpVersionNotSupported()->isFixed());
    }
}
