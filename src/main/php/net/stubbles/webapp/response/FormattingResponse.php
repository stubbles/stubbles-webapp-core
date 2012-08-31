<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
/**
 * Response which is able to format the response body if it is not a string.
 *
 * @since  2.0.0
 */
interface FormattingResponse extends Response
{
    /**
     * writes a Forbidden message into response body
     *
     * @return  FormattingResponse
     */
    public function writeForbiddenError();

    /**
     * writes a Not Found message into response body
     *
     * @return  FormattingResponse
     */
    public function writeNotFoundError();

    /**
     * writes a Method Not Allowed message into response body
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  FormattingResponse
     */
    public function writeMethodNotAllowedError($requestMethod, array $allowedMethods);

    /**
     * writes an Internal Server Error message into response body
     *
     * @param   string  $errorMessage
     * @return  FormattingResponse
     */
    public function writeInternalServerError($errorMessage);
}
?>