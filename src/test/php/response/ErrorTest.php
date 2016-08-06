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
use bovigo\callmap\NewInstance;
use stubbles\input\errors\ParamErrors;
use stubbles\input\errors\messages\LocalizedMessage;
use stubbles\input\errors\messages\ParamErrorMessages;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
/**
 * Tests for stubbles\webapp\response\Error.
 *
 * @group  response_1
 * @since  6.2.0
 */
class ErrorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canCreateInstanceFromListOfParamErrors()
    {
        $paramErrors = new ParamErrors();
        $paramErrors->append('foo', 'FIELD_EMPTY');
        $paramErrors->append('foo', 'STRING_TOO_SHORT', ['baz' => 303]);
        $paramErrors->append('bar', 'STRING_TOO_LONG');
        $errorMessages = NewInstance::of(ParamErrorMessages::class)
                ->returns(['messageFor' => onConsecutiveCalls(
                        new LocalizedMessage('en_*', 'foo empty'),
                        new LocalizedMessage('en_*', 'foo_too_short'),
                        new LocalizedMessage('en_*', 'bar_too_long')
                )]);
        assert(
                json_encode(Error::inParams($paramErrors, $errorMessages)),
                equals('{"error":{"foo":{"field":"foo","errors":[{"id":"FIELD_EMPTY","details":[],"message":"foo empty"},{"id":"STRING_TOO_SHORT","details":{"baz":303},"message":"foo_too_short"}]},"bar":{"field":"bar","errors":[{"id":"STRING_TOO_LONG","details":[],"message":"bar_too_long"}]}}}')
        );
    }
}
