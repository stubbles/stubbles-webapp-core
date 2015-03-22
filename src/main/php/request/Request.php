<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\request;
use stubbles\webapp\session\Session;
/**
 * Interface for web applications requests.
 *
 * @api
 */
interface Request extends \stubbles\input\Request
{
    /**
     * returns id of the request
     *
     * The id of the request may come from an optional X-Request-ID header. The
     * value must be between 20 and 200 characters, and consist of ASCII
     * letters, digits, or the characters +, /, =, and -. Invalid or missing ids
     * will be ignored and replaced with generated ones.
     *
     * @return  string
     * @since   4.2.0
     * @see     https://devcenter.heroku.com/articles/http-request-id
     */
    public function id();

    /**
     * checks whether request was made using ssl
     *
     * @return  bool
     */
    public function isSsl();

    /**
     * returns HTTP protocol version of request
     *
     * If no SERVER_PROTOCOL is present it is assumed that the protocol version
     * is HTTP/1.0. In case the SERVER_PROTOCOL does not denote a valid HTTP
     * version according to http://tools.ietf.org/html/rfc7230#section-2.6 the
     * return value will be null.
     *
     * @return  \stubbles\peer\http\HttpVersion
     * @since   2.0.2
     */
    public function protocolVersion();

    /**
     * returns the ip which issued the request originally
     *
     * The originating IP address is the IP address of the client which issued
     * the request. In case the request was routed via several proxies it will
     * still return the real client IP, and not the IP address of the last proxy
     * in the chain.
     *
     * Please note that the method relies on the values of REMOTE_ADDR provided
     * by PHP and the X-Forwarded-For header. If none of these is present the
     * return value will be null. Additionally, if the value of these headers
     * does not contain a syntactically correct IP address, the return value
     * will be null.
     *
     * Also, the return value might not neccessarily be an existing IP address
     * nor the real IP address of the client, as it may be spoofed.
     *
     * @return  \stubbles\peer\IpAddress
     * @since   3.0.0
     */
    public function originatingIpAddress();

    /**
     * returns the user agent which issued the request
     *
     * Please be aware that user agents can fake their appearance.
     *
     * The bot recognition will recognize Googlebot, Bing (including former
     * msnbot, Yahoo! Slurp, Pingdom and Yandex by default. Additional
     * signatures can be passed, they must contain a regular expression which
     * matches the user agent of a bot.
     *
     * @param   string[]  $botSignatures  optional  additional list of bot user agent signatures
     * @return  \stubbles\input\web\useragent\UserAgent
     * @since   4.1.0
     */
    public function userAgent($botSignatures = []);

    /**
     * returns the uri of the request
     *
     * In case the composed uri for this request does not denote a valid HTTP
     * uri a RuntimeException is thrown. If you came this far but the request
     * is for an invalid HTTP uri something is completely wrong.
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function uri();

    /**
     * return a list of all header names registered in this request
     *
     * @return  string[]
     * @since   1.3.0
     */
    public function headerNames();

    /**
     * checks whether a request header is set
     *
     * @param   string  $headerName
     * @return  bool
     * @since   1.3.0
     */
    public function hasHeader($headerName);

    /**
     * checks whether a request header or it's redirect equivalent is set
     *
     * A redirect header is one that starts with REDIRECT_ and has most likely
     * a different value after a redirection happened than the original header.
     * The method will try to use the header REDIRECT_$headerName first, but
     * falls back to $headerName when REDIRECT_$headerName  is not present.
     *
     * @param   string  $headerName
     * @return  bool
     * @since   3.1.1
     */
    public function hasRedirectHeader($headerName);

    /**
     * returns error collection for request headers
     *
     * @return  \stubbles\input\errors\ParamErrors
     * @since   1.3.0
     */
    public function headerErrors();

    /**
     * checks whether a request value from headers is valid or not
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueValidator
     * @since   1.3.0
     */
    public function validateHeader($headerName);

    /**
     * checks whether a request value from redirect headers is valid or not
     *
     * A redirect header is one that starts with REDIRECT_ and has most likely
     * a different value after a redirection happened than the original header.
     * The method will try to use the header REDIRECT_$headerName first, but
     * falls back to $headerName when REDIRECT_$headerName  is not present.
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueValidator
     * @since   3.1.0
     */
    public function validateRedirectHeader($headerName);

    /**
     * returns request value from headers for filtering or validation
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueReader
     * @since   1.3.0
     */
    public function readHeader($headerName);

    /**
     * returns request value from headers for filtering or validation
     *
     * A redirect header is one that starts with REDIRECT_ and has most likely
     * a different value after a redirection happened than the original header.
     * The method will try to use the header REDIRECT_$headerName first, but
     * falls back to $headerName when REDIRECT_$headerName  is not present.
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueReader
     * @since   3.1.0
     */
    public function readRedirectHeader($headerName);

    /**
     * return an array of all cookie names registered in this request
     *
     * @return  string[]
     * @since   1.3.0
     */
    public function cookieNames();

    /**
     * checks whether a request cookie is set
     *
     * @param   string  $cookieName
     * @return  bool
     * @since   1.3.0
     */
    public function hasCookie($cookieName);

    /**
     * returns error collection for request cookies
     *
     * @return  \stubbles\input\errors\ParamErrors
     * @since   1.3.0
     */
    public function cookieErrors();

    /**
     * checks whether a request value from cookie is valid or not
     *
     * @param   string  $cookieName  name of cookie
     * @return  \stubbles\input\ValueValidator
     * @since   1.3.0
     */
    public function validateCookie($cookieName);

    /**
     * returns request value from cookies for filtering or validation
     *
     * @param   string  $cookieName  name of cookie
     * @return  \stubbles\input\ValueReader
     * @since   1.3.0
     */
    public function readCookie($cookieName);

    /**
     * returns an input stream which allows to read the request body
     *
     * It returns the data raw and unsanitized, any filtering and validating
     * must be done by the caller.
     *
     * @since   6.0.0
     * @return  \stubbles\streams\InputStream
     */
    public function body();

    /**
     * attaches session to request
     *
     * @internal
     * @param   \stubbles\webapp\session\Session  $session  optional
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    public function attachSession(Session $session = null);

    /**
     * checks if a session is attached to the request
     *
     * @return  bool
     * @since   6.0.0
     */
    public function hasSessionAttached();

    /**
     * returns attached session
     *
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    public function attachedSession();
}
