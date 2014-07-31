<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\websession;
use stubbles\input\web\WebRequest;
use stubbles\webapp\response\Cookie;
use stubbles\webapp\response\Response;
use stubbles\webapp\session\id\SessionId;
/**
 * Session id which is stored in a cookie.
 *
 * @since  2.0.0
 */
class WebBoundSessionId implements SessionId
{
    /**
     * regular expression to validate the session id
     *
     * @var  string
     */
    const SESSION_ID_REGEX = '/^([a-zA-Z0-9]{32})$/D';
    /**
     * request instance
     *
     * @type  \stubbles\input\web\WebRequest
     */
    private $request;
    /**
     * response instance
     *
     * @type  \stubbles\webapp\response\Response
     */
    private $response;
    /**
     * name of session
     *
     * @type  string
     */
    private $sessionName;
    /**
     * actual id
     *
     * @type  string
     */
    private $id;

    /**
     * constructor
     *
     * @param  \stubbles\input\web\WebRequest      $request
     * @param  \stubbles\webapp\response\Response  $response
     * @param  string                              $sessionName
     */
    public function __construct(WebRequest $request, Response $response, $sessionName)
    {
        $this->request     = $request;
        $this->response    = $response;
        $this->sessionName = $sessionName;
    }

    /**
     * returns session name
     *
     * @return  string
     */
    public function name()
    {
        return $this->sessionName;
    }

    /**
     * reads session id
     *
     * @return  string
     */
    public function __toString()
    {
        if (null === $this->id) {
            $this->id = $this->read();
            if (null === $this->id) {
                $this->id = $this->create();
            }
        }

        return $this->id;
    }

    /**
     * reads session id
     *
     * @return  string
     */
    private function read()
    {
        if ($this->request->hasParam($this->sessionName)) {
            return $this->request->readParam($this->sessionName)->ifSatisfiesRegex(self::SESSION_ID_REGEX);
        } elseif ($this->request->hasCookie($this->sessionName)) {
            return $this->request->readCookie($this->sessionName)->ifSatisfiesRegex(self::SESSION_ID_REGEX);
        }

        return null;
    }

    /**
     * creates session id
     *
     * @return  string
     */
    private function create()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * stores session id for given session name
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function regenerate()
    {
        $this->id = $this->create();
        $this->response->addCookie(Cookie::create($this->sessionName, $this->id)
                                         ->forPath('/')
        );
        return $this;
    }

    /**
     * invalidates session id
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function invalidate()
    {
        $this->response->removeCookie($this->sessionName);
        return $this;
    }
}
