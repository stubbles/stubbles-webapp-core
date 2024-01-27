<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp {
    use stubbles\webapp\htmlpassthrough\HtmlFilePassThrough;
    use stubbles\webapp\htmlpassthrough\SessionBasedHtmlFilePassThrough;
    /**
     * returns class name for session based HTML file pass through processor
     *
     * @since  4.0.0
     */
    function htmlPassThrough(): string
    {
        return HtmlFilePassThrough::class;
    }

    /**
     * returns class name for session based HTML file pass through processor
     *
     * @since  4.0.0
     */
    function sessionBasedHtmlPassThrough(): string
    {
        return SessionBasedHtmlFilePassThrough::class;
    }

}
namespace stubbles\webapp\session {
    use stubbles\webapp\Request;
    use stubbles\webapp\Response;
    use stubbles\webapp\session\NullSession;
    use stubbles\webapp\session\WebSession;
    use stubbles\webapp\session\id\NoneDurableSessionId;
    use stubbles\webapp\session\id\WebBoundSessionId;
    use stubbles\webapp\session\storage\ArraySessionStorage;
    use stubbles\webapp\session\storage\NativeSessionStorage;

    /**
     * returns a callable which creates a session based on php's session implementation
     *
     * @since  4.0.0
     */
    function native(string $sessionName, string $fingerPrint): WebSession
    {
        $native = new NativeSessionStorage($sessionName);
        return new WebSession($native, $native, $fingerPrint);
    }

    /**
     * creates a session which is not durable between requests
     *
     * The resulting session will create a new session id with each request. It
     * does not store any values between requests.
     *
     * @since  4.0.0
     */
    function noneDurable(): WebSession
    {
        return new WebSession(
            new ArraySessionStorage(),
            new NoneDurableSessionId(),
            uniqid()
        );
    }

    /**
     * creates a session which does nothing, not even storing any values
     *
     * @since   5.0.0
     */
    function nullSession(): NullSession
    {
        return new NullSession(new NoneDurableSessionId());
    }

    /**
     * creates a session that is durable between requests but does not store any values
     *
     * The resulting session will keep the session id between requests, but not
     * any value that is stored within the session.
     *
     * @since   4.0.0
     */
    function noneStoring(Request $request, Response $response, string $sessionCookieName): NullSession
    {
        return new NullSession(
            new WebBoundSessionId($request, $response, $sessionCookieName)
        );
    }
}
