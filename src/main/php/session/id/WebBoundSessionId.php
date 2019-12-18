<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\id;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\response\Cookie;
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
     * @var  \stubbles\webapp\Request
     */
    private $request;
    /**
     * response instance
     *
     * @var  \stubbles\webapp\Response
     */
    private $response;
    /**
     * name of session
     *
     * @var  string
     */
    private $sessionName;
    /**
     * actual id
     *
     * @var  string
     */
    private $id;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\Request   $request
     * @param  \stubbles\webapp\Response  $response
     * @param  string                     $sessionName
     */
    public function __construct(Request $request, Response $response, string $sessionName)
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
    public function name(): string
    {
        return $this->sessionName;
    }

    /**
     * reads session id
     *
     * @return  string
     */
    public function __toString(): string
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
     * @return  string|null
     */
    private function read(): ?string
    {
        if ($this->request->hasParam($this->sessionName)) {
            return $this->request->readParam($this->sessionName)->ifMatches(self::SESSION_ID_REGEX);
        } elseif ($this->request->hasCookie($this->sessionName)) {
            return $this->request->readCookie($this->sessionName)->ifMatches(self::SESSION_ID_REGEX);
        }

        return null;
    }

    /**
     * creates session id
     *
     * @return  string
     */
    private function create(): string
    {
        return md5(uniqid((string) rand(), true));
    }

    /**
     * stores session id for given session name
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function regenerate(): SessionId
    {
        $this->id = $this->create();
        $this->response->addCookie(
                Cookie::create($this->sessionName, $this->id)->forPath('/')
        );
        return $this;
    }

    /**
     * invalidates session id
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function invalidate(): SessionId
    {
        $this->response->removeCookie($this->sessionName);
        return $this;
    }
}
