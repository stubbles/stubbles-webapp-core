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
use net\stubbles\lang\BaseObject;
/**
 * Creates response with correct HTTP version based on a given HTTP version.
 *
 * If the HTTP version is not supported it will create a response with version
 * HTTP/1.1 and status code set to 500, additionally cancelling the request.
 */
class ResponseCreator extends BaseObject
{
    /**
     * creates response instance
     *
     * @param   string  $httpProtocol   http protocol to create response for
     * @param   string  $responseClass  concrete response class to create
     * @return  Response
     */
    public static function create($httpProtocol, $responseClass = 'net\stubbles\webapp\response\WebResponse')
    {
        $minor      = null;
        $scanResult = sscanf($httpProtocol, 'HTTP/%*[1].%[01]', $minor);
        if (2 != $scanResult) {
            $response = new $responseClass();
            $response->setStatusCode(505);
            $response->write('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1');
        } else {
            $response = new $responseClass('1.' . ((int) $minor));
        }

        return $response;
    }
}
?>