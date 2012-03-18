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
use net\stubbles\lang\exception\IllegalArgumentException;
/**
 * Serializer for response instances.
 *
 * @since  1.7.0
 * @Singleton
 */
class ResponseSerializer extends BaseObject
{
    /**
     * unserializes string into a response
     *
     * @param   string
     * @return  Response
     * @throws  IllegalArgumentException
     */
    public function unserialize($serialized)
    {
        $response = @unserialize($serialized);
        if ($response instanceof Response) {
            return $response;
        }

        throw new IllegalArgumentException('Invalid serialized response.');
    }

    /**
     * serializes response into a string
     *
     * @param   Response
     * @return  string
     */
    public function serialize(Response $response)
    {
        return serialize($response);
    }

    /**
     * serialize response without cookies
     *
     * @param   Response  $response
     * @return  string
     */
    public function serializeWithoutCookies(Response $response)
    {
        $class = $response->getClassName();
        $other = new $class($response->getVersion());
        /* @var $other net\stubbles\webapp\io\response\Response */
        $other->write($response->getBody());
        foreach ($response->getHeaders() as $name => $value) {
            $other->addHeader($name, $value);
        }

        return serialize($other);
    }
}
?>