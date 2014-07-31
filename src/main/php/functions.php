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
namespace stubbles\webapp\session {
    use stubbles\input\web\WebRequest;
    use stubbles\webapp\response\Response;
    use stubbles\webapp\session\NullSession;
    use stubbles\webapp\websession\WebBoundSessionId;

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
        return function(WebRequest $request, Response $response) use($sessionCookieName)
               {
                   return new NullSession(new WebBoundSessionId($request, $response, $sessionCookieName));
               };
    }
}

