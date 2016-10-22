<?php
namespace Webiik;

class Csrf
{
    /**
     * @var string
     */
    private $name = 'csrf_token';

    /**
     * @var int
     */
    private $strength = 8;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Sessions
     */
    private $sessions;

    /**
     * Csrf constructor.
     * @param Token $token
     * @param Sessions $sessions
     */
    public function __construct(Token $token, Sessions $sessions)
    {
        $this->token = $token;
        $this->sessions = $sessions;
    }

    /**
     * @param string $name
     */
    public function setTokenName($name)
    {
        $this->name = $name;
    }

    /**
     * @param int $strength
     */
    public function setTokenStrength($strength)
    {
        $this->strength = $strength;
    }

    /**
     * Set token in to session
     */
    public function setToken()
    {
        $this->sessions->setToSession($this->name, $this->token->generate($this->strength));
    }

    /**
     * Set token into session and return hidden input
     * @return string
     */
    public function getHiddenInput()
    {
        return '<input type="hidden" name="' . $this->name . '" value="' . $this->getToken() . '"/>';
    }

    /**
     * Get token value
     * @return bool|string
     */
    public function getToken()
    {
        return $this->sessions->getFromSession($this->name);
    }

    /**
     * Get token name
     * @return string
     */
    public function getTokenName()
    {
        return $this->name;
    }

    /**
     * Delete token from session
     */
    public function deleteToken()
    {
        $this->sessions->delFromSession($this->name);
    }

    /**
     * Compare token with token in session
     * @param string $token
     * @return bool
     */
    public function validateToken($token)
    {
        return $this->token->compare($this->getToken(), $token) ? true : false;
    }
}