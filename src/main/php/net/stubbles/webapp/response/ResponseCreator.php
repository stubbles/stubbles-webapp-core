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
 */
class ResponseCreator extends BaseObject
{
    /**
     * creates response instance
     *
     * If the HTTP version is not supported it will create a response with version
     * HTTP/1.1 and status code set to 500.
     *
     * @param   string  $httpProtocol   http protocol to create response for
     * @param   string  $responseClass  concrete response class to create
     * @return  Response
     */
    public static function createForProtocol($httpProtocol, $responseClass = 'net\stubbles\webapp\response\WebResponse')
    {
        $minor = null;
        if (1 != sscanf($httpProtocol, 'HTTP/1.%[01]', $minor)) {
            $response = self::createForVersion(null, $responseClass);
        } else {
            $response = self::createForVersion('1.' . $minor, $responseClass);
        }

        return $response;
    }

    /**
     * creates response instance
     *
     * @param   string  $httpVersion   http protocol to create response for
     * @param   string  $responseClass  concrete response class to create
     * @return  Response
     */
    public static function createForVersion($httpVersion, $responseClass = 'net\stubbles\webapp\response\WebResponse')
    {
        if (empty($httpVersion)) {
            $response = new $responseClass('1.1');
            $response->setStatusCode(505)
                     ->write('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1');
        } else {
            $response = new $responseClass($httpVersion);
        }

        return $response;
    }
}
?>