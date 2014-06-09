<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  tubbles\webapp
 */
namespace stubbles\webapp\response;
/**
 * Container for cookies to be send out to the user.
 *
 * Cookies are used to store user-related data within the user-agent
 * e.g. to help detecting that requests are done by the same user.
 * Common applications are session cookies or low-level signon help.
 *
 * @link  http://wp.netscape.com/newsref/std/cookie_spec.html
 * @link  http://www.faqs.org/rfcs/rfc2109.html
 */
class Cookie
{
    /**
     * name of the cookie
     *
     * @type  string
     */
    private $name     = '';
    /**
     * value of the cookie
     *
     * @type  string
     */
    private $value    = '';
    /**
     * timestamp when cookie expires
     *
     * @type  int
     */
    private $expires  = 0;
    /**
     * path for which the cookie should be available
     *
     * @type  string
     */
    private $path     = null;
    /**
     * domain where this cookie will be available
     *
     * @type  string
     */
    private $domain   = null;
    /**
     * switch whether cookie should only be used in secure connections
     *
     * @type  bool
     */
    private $secure   = false;
    /**
     * switch whether cookie should only be accessible through http
     *
     * @type  bool
     */
    private $httpOnly = true;

    /**
     * constructor
     *
     * @param  string  $name   name of the cookie
     * @param  string  $value  value of the cookie
     */
    public function __construct($name, $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * creates the cookie
     *
     * @param   string  $name   name of the cookie
     * @param   string  $value  value of the cookie
     * @return  Cookie
     */
    public static function create($name, $value)
    {
        return new self($name, $value);
    }

    /**
     * set the timestamp when the cookie will expire
     *
     * Please note that $expires must be a timestamp in the future.
     *
     * @param   int  $expires  timestamp in seconds since 1970
     * @return  Cookie
     */
    public function expiringAt($expires)
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * sets the cookie to expire after given amount of seconds
     *
     * The method will add the current timestamp to the given amount of seconds.
     *
     * @param   int   $seconds
     * @return  Cookie
     * @since   1.5.0
     */
    public function expiringIn($seconds)
    {
        $this->expires = time() + $seconds;
        return $this;
    }

    /**
     * set the path for which the cookie should be available
     *
     * @param   string  $path
     * @return  Cookie
     */
    public function forPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * set the domain where this cookie will be available
     *
     * @param   string  $domain
     * @return  Cookie
     */
    public function forDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * set cookie only on secure connections
     *
     * @return  Cookie
     */
    public function restrictToSsl()
    {
        $this->secure = true;
        return $this;
    }

    /**
     * disable setting the cookie as http only
     *
     * @return  Cookie
     */
    public function disableHttpOnly()
    {
        $this->httpOnly = false;
        return $this;
    }

    /**
     * returns name of cookie
     *
     * @return  string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * returns name of cookie
     *
     * @return  string
     * @deprecated since 4.0.0, use name() instead, will be removed with 5.0.0
     */
    public function getName()
    {
        return $this->name();
    }

    /**
     * returns value of cookie
     *
     * @return  string
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * returns value of cookie
     *
     * @return  string
     * @deprecated since 4.0.0, use value() instead, will be removed with 5.0.0
     */
    public function getValue()
    {
        return $this->value();
    }

    /**
     * returns expiration timestamp of cookie
     *
     * @return  int
     */
    public function expiration()
    {
        return $this->expires;
    }

    /**
     * returns expiration timestamp of cookie
     *
     * @return  int
     * @deprecated since 4.0.0, use expiration() instead, will be removed with 5.0.0
     */
    public function getExpiration()
    {
        return $this->expiration();
    }

    /**
     * returns path of cookie
     *
     * @return  string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * returns path of cookie
     *
     * @return  string
     * @deprecated since 4.0.0, use path() instead, will be removed with 5.0.0
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * returns domain of cookie
     *
     * @return  string
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * returns domain of cookie
     *
     * @return  string
     * @deprecated since 4.0.0, use domain() instead, will be removed with 5.0.0
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * checks whether cookie should only be sst on secure connections
     *
     * @return  bool
     */
    public function isRestrictedToSsl()
    {
        return $this->secure;
    }

    /**
     * checks whether cookie should only be accessible through http
     *
     * @return  bool
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * sends the cookie
     */
    public function send()
    {
        setcookie($this->name, $this->value, $this->expires, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }
}
