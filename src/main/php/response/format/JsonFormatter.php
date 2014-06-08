<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\format;
/**
 * Formats resource to JSON.
 *
 * @since  1.1.0
 */
class JsonFormatter implements Formatter
{
    /**
     * formats resource for response
     *
     * @param   mixed   $resource
     * @return  string
     */
    public function format($resource)
    {
        return json_encode($resource);
    }

    /**
     * write error message about 403 Forbidden error
     *
     * @return  string
     */
    public function formatForbiddenError()
    {
        return json_encode(['error' => 'You are not allowed to access this resource.']);
    }

    /**
     * write error message about 404 Not Found error
     *
     * @return  string
     */
    public function formatNotFoundError()
    {
        return json_encode(['error' => 'Given resource could not be found.']);
    }

    /**
     * write error message about 405 Method Not Allowed error
     *
     * @param   string    $requestMethod   original request method
     * @param   string[]  $allowedMethods  list of allowed methods
     * @return  string
     */
    public function formatMethodNotAllowedError($requestMethod, array $allowedMethods)
    {
        return json_encode(['error' => 'The given request method ' . strtoupper($requestMethod) . ' is not valid. Please use one of ' . join(', ', $allowedMethods) . '.']);
    }

    /**
     * write error message about 500 Internal Server error
     *
     * @param   string  $message
     * @return  string
     */
    public function formatInternalServerError($message)
    {
        return json_encode(['error' => 'Internal Server Error: ' . $message]);
    }
}
