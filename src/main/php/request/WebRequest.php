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
use stubbles\input\AbstractRequest;
use stubbles\input\Params;
use stubbles\input\ValueReader;
use stubbles\input\ValueValidator;
use stubbles\peer\IpAddress;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpUri;
use stubbles\peer\http\HttpVersion;
use stubbles\streams\StandardInputStream;
use stubbles\webapp\session\Session;
/**
 * Request implementation for web applications.
 */
class WebRequest extends AbstractRequest implements Request
{
    /**
     * generated id for request if no or an invalid X-Request-ID header is present
     *
     * @type  string
     */
    private $id;
    /**
     * list of params
     *
     * @type  \stubbles\input\Params
     */
    private $headers;
    /**
     * list of params
     *
     * @type  \stubbles\input\Params
     */
    private $cookies;
    /**
     * reference to attached session
     *
     * @type  \stubbles\webapp\session\Session
     */
    private $session;

    /**
     * constructor
     *
     * @param  array     $params      map of request parameters
     * @param  array     $headers     map of request headers
     * @param  array     $cookies     map of request cookies
     */
    public function __construct(array $params, array $headers, array $cookies)
    {
        parent::__construct(new Params($params));
        $this->headers = new Params($headers);
        $this->cookies = new Params($cookies);
    }

    /**
     * creates an instance from raw data, meaning $_GET/$_POST, $_SERVER and $_COOKIE
     *
     * @api
     * @return  \stubbles\webapp\request\Request
     */
    public static function fromRawSource()
    {
        if (isset($_SERVER['REQUEST_METHOD'])
                && strtoupper(trim($_SERVER['REQUEST_METHOD'])) === Http::POST) {
            $params = $_POST;
        } else {
            $params = $_GET;
        }

        return new self($params, $_SERVER, $_COOKIE);
    }

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
    public function id()
    {
        if (null !== $this->id) {
            return $this->id;
        }

        if ($this->headers->contain('HTTP_X_REQUEST_ID')) {
            $this->id = $this->readHeader('HTTP_X_REQUEST_ID')
                    ->ifSatisfiesRegex('~^([a-zA-Z0-9+/=-]{20,200})$~');
        }

        if (null === $this->id) {
            $this->id = substr(str_shuffle(md5(microtime())), 0, 25);
        }

        return $this->id;
    }

    /**
     * returns the request method
     *
     * @return  string
     */
    public function method()
    {
        return strtoupper($this->headers->value('REQUEST_METHOD'));
    }

    /**
     * checks whether request was made using ssl
     *
     * @return  bool
     */
    public function isSsl()
    {
        return $this->headers->contain('HTTPS');
    }

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
    public function protocolVersion()
    {
        if (!$this->headers->contain('SERVER_PROTOCOL')) {
            return new HttpVersion(1, 0);
        }

        try {
            return HttpVersion::fromString($this->headers->value('SERVER_PROTOCOL'));
        } catch (\InvalidArgumentException $ex) {
            return null;
        }
    }

    /**
     * returns the ip address which issued the request originally
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
    public function originatingIpAddress()
    {
        try {
            if ($this->headers->contain('HTTP_X_FORWARDED_FOR')) {
                $remoteAddresses = explode(',', $this->headers->value('HTTP_X_FORWARDED_FOR'));
                return new IpAddress(trim($remoteAddresses[0]));
            }

            if ($this->headers->contain('REMOTE_ADDR')) {
                return new IpAddress($this->headers->value('REMOTE_ADDR'));
            }
        } catch (\InvalidArgumentException $iae) {
            // treat as if no ip address available
        }

        return null;
    }

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
     * @return  \stubbles\webapp\request\UserAgent
     * @since   4.1.0
     */
    public function userAgent($botSignatures = [])
    {
        return new UserAgent(
                $this->headers->get('HTTP_USER_AGENT')->value(),
                $this->cookies->count() > 0,
                $botSignatures
        );
    }

    /**
     * returns the uri of the request
     *
     * In case the composed uri for this request does not denote a valid HTTP
     * uri a stubbles\peer\MalformedUriException is thrown. If you came this far
     * but the request is for an invalid HTTP uri something is completely wrong,
     * most likely the request tries to find out if you have a security issue
     * because the request uri data is not checked properly. It is advisable to
     * respond with a 400 Bad Request in such cases.
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function uri()
    {
        $host = $this->headers->value('HTTP_HOST');
        return HttpUri::fromParts(
                (($this->headers->contain('HTTPS')) ? (Http::SCHEME_SSL) : (Http::SCHEME)),
                $host,
                (strstr($host, ':') === false ? $this->headers->value('SERVER_PORT') : null),
                $this->headers->value('REQUEST_URI') // already contains query string
        );
    }

    /**
     * return an array of all header names registered in this request
     *
     * @return  string[]
     * @since   1.3.0
     */
    public function headerNames()
    {
        return $this->headers->names();
    }

    /**
     * checks whether a request header is set
     *
     * @param   string  $headerName
     * @return  bool
     * @since   1.3.0
     */
    public function hasHeader($headerName)
    {
        return $this->headers->contain($headerName);
    }

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
    public function hasRedirectHeader($headerName)
    {
        return $this->hasHeader('REDIRECT_' . $headerName) || $this->hasHeader($headerName);
    }

    /**
     * returns error collection for request headers
     *
     * @return  \stubbles\input\errors\ParamErrors
     * @since   1.3.0
     */
    public function headerErrors()
    {
        return $this->headers->errors();
    }

    /**
     * checks whether a request value from headers is valid or not
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueValidator
     * @since   1.3.0
     */
    public function validateHeader($headerName)
    {
        return new ValueValidator($this->headers->get($headerName));
    }

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
    public function validateRedirectHeader($headerName)
    {
        if ($this->headers->contain('REDIRECT_' . $headerName)) {
            return $this->validateHeader('REDIRECT_' . $headerName);
        }

        return $this->validateHeader($headerName);
    }

    /**
     * returns request value from headers for filtering or validation
     *
     * @param   string  $headerName  name of header
     * @return  \stubbles\input\ValueReader
     * @since   1.3.0
     */
    public function readHeader($headerName)
    {
        return new ValueReader(
                $this->headers->errors(),
                $this->headers->get($headerName)
        );
    }

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
    public function readRedirectHeader($headerName)
    {
        if ($this->headers->contain('REDIRECT_' . $headerName)) {
            return $this->readHeader('REDIRECT_' . $headerName);
        }

        return $this->readHeader($headerName);
    }

    /**
     * return an array of all cookie names registered in this request
     *
     * @return  string[]
     * @since   1.3.0
     */
    public function cookieNames()
    {
        return $this->cookies->names();
    }

    /**
     * checks whether a request cookie is set
     *
     * @param   string  $cookieName
     * @return  bool
     * @since   1.3.0
     */
    public function hasCookie($cookieName)
    {
        return $this->cookies->contain($cookieName);
    }

    /**
     * returns error collection for request cookies
     *
     * @return  \stubbles\input\errors\ParamErrors
     * @since   1.3.0
     */
    public function cookieErrors()
    {
        return $this->cookies->errors();
    }

    /**
     * checks whether a request value from cookie is valid or not
     *
     * @param   string  $cookieName  name of cookie
     * @return  \stubbles\input\ValueValidator
     * @since   1.3.0
     */
    public function validateCookie($cookieName)
    {
        return new ValueValidator($this->cookies->get($cookieName));
    }

    /**
     * returns request value from cookies for filtering or validation
     *
     * @param   string  $cookieName  name of cookie
     * @return  \stubbles\input\ValueReader
     * @since   1.3.0
     */
    public function readCookie($cookieName)
    {
        return new ValueReader(
                $this->cookies->errors(),
                $this->cookies->get($cookieName)
        );
    }

    /**
     * returns an input stream which allows to read the request body
     *
     * It returns the data raw and unsanitized, any filtering and validating
     * must be done by the caller.
     *
     * @since   6.0.0
     * @return  \stubbles\streams\InputStream
     */
    public function body()
    {
        return new StandardInputStream();
    }

    /**
     * attaches session to request
     *
     * @internal
     * @param   \stubbles\webapp\session\Session  $session
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    public function attachSession(Session $session = null)
    {
        $this->session = $session;
        return $session;
    }

    /**
     * checks if a session is attached to the request
     *
     * @return  bool
     * @since   6.0.0
     */
    public function hasSessionAttached()
    {
        return null !== $this->session;
    }

    /**
     * returns attached session
     *
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    public function attachedSession()
    {
        return $this->session;
    }
}
