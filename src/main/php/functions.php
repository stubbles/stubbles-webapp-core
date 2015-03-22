<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp {
    /**
     * returns class name for session based HTML file pass through processor
     *
     * @return  string
     * @since   4.0.0
     */
    function htmlPassThrough()
    {
        return 'stubbles\webapp\processor\HtmlFilePassThrough';
    }

    /**
     * returns class name for session based HTML file pass through processor
     *
     * @return  string
     * @since   4.0.0
     */
    function sessionBasedHtmlPassThrough()
    {
        return 'stubbles\webapp\processor\SessionBasedHtmlFilePassThrough';
    }

}
namespace stubbles\webapp\websession {
    use stubbles\webapp\request\Request;
    use stubbles\webapp\response\Response;
    use stubbles\webapp\session\NullSession;
    use stubbles\webapp\websession\WebBoundSessionId;

    /**
     * returns a callable which creates a session based on php's session implementation
     *
     * @param   string  $sessionName  name of session to create
     * @return  callable
     * @since   4.0.0
     */
    function native($sessionName)
    {
        return function(Request $request) use($sessionName)
               {
                    return \stubbles\webapp\session\native($sessionName, md5($request->readHeader('HTTP_USER_AGENT')->unsecure()));
               };
    }

    /**
     * returns a callable which creates a session that is not durable between requests
     *
     * The resulting session will create a new session id with each request. It
     * does not store any values between requests.
     *
     * @return  callable
     * @since   4.0.0
     */
    function noneDurable()
    {
        return function()
               {
                   return \stubbles\webapp\session\noneDurable();
               };
    }

    /**
     * returns a callable which creates a session that is durable between requests but does not store any values
     *
     * The resulting session will keep the session id between requests, but not
     * any value that is stored within the session.
     *
     * @param   string  $sessionCookieName  name of cookie to store session id in
     * @return  callable
     * @since   4.0.0
     */
    function noneStoring($sessionCookieName)
    {
        return function(Request $request, Response $response) use($sessionCookieName)
               {
                   return new NullSession(new WebBoundSessionId($request, $response, $sessionCookieName));
               };
    }

    /**
     * returns a callable which creates a session which does nothing, not even storing any values
     *
     * @return  callable
     * @since   5.0.0
     */
    function nullSession()
    {
        return function()
               {
                   return \stubbles\webapp\session\nullSession();
               };
    }
}

