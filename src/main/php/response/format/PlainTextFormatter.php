<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response\format;
/**
 * Formats resource as plain text.
 *
 * @since  1.1.2
 */
class PlainTextFormatter implements Formatter
{
    /**
     * formats resource for response
     *
     * @param   mixed   $resource
     * @return  string
     */
    public function format($resource)
    {
        if (is_object($resource) && method_exists($resource, '__toString')) {
            return (string) $resource;
        }

        if (is_object($resource) || is_array($resource)) {
            return var_export($resource, true);
        }

        if (is_bool($resource) && $resource) {
            return 'true';
        }

        if (is_bool($resource) && !$resource) {
            return 'false';
        }

        return (string) $resource;
    }

    /**
     * write error message about 403 Forbidden error
     *
     * @return  string
     */
    public function formatForbiddenError()
    {
        return 'You are not allowed to access this resource.';
    }

    /**
     * write error message about 404 Not Found error
     *
     * @return  string
     */
    public function formatNotFoundError()
    {
        return 'Given resource could not be found.';
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
        return 'The given request method ' . strtoupper($requestMethod) . ' is not valid. Please use one of ' . join(', ', $allowedMethods) . '.';
    }

    /**
     * write error message about 500 Internal Server error
     *
     * @param   string  $message
     * @return  string
     */
    public function formatInternalServerError($message)
    {
        return 'Internal Server Error: ' . $message;
    }
}
