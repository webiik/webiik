<?php
namespace Webiik;

/**
 * Class Flash
 * @package Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Flash
{
    /**
     * @var Arr
     */
    private $arr;

    /**
     * @var Session
     */
    private $sessions;

    /** @var array All 'now' messages */
    private $messages = [];

    /** @var array All 'next' messages */
    private $messagesNext = [];

    private $wraps = [];

    /**
     * Flash constructor.
     * @param $sessions Session
     * @param $arr Arr
     */
    public function __construct(Session $sessions, Arr $arr)
    {
        $this->sessions = $sessions;
        $this->arr = $arr;
    }

    /**
     * Set HTML wrap eg. <div class='msg'>{{ msg }}</div>
     * @param $type
     * @param $wrap
     */
    public function setWrap($type, $wrap)
    {
        $this->wraps[$type] = $wrap;
    }

    /**
     * Set message into 'now' messages
     * @param $type
     * @param $message
     */
    public function addFlashNow($type, $message)
    {
        $this->messages[$type][] = $message;
    }

    /**
     * Set message into 'next' messages and into session
     * @param $type
     * @param $message
     */
    public function addFlashNext($type, $message)
    {
        $this->messagesNext[$type][] = $message;
        $this->sessions->addToSession('messages.' . $type, [$message]);
    }

    /**
     * Get all messages that should be displayed
     * @param null $type
     * @return array
     */
    public function getFlashes($type = null)
    {
        // Get all messages from session
        $sessionMessages = $this->sessions->getFromSession('messages');
        if (!is_array($sessionMessages)) $sessionMessages = [];

        // Get next messages only for current request
        $nextMessages = $this->arr->diffMultiAB($sessionMessages, $this->messagesNext);

        // Unset next messages from previous request
        $this->unsetFromSession($nextMessages);

        // Get all current messages
        $messages = array_merge_recursive($nextMessages, $this->messages);

        if ($type) {
            return isset($messages[$type]) ? [$type => $messages[$type]] : [];
        }

        return $messages;
    }

    /**
     * Get all messages that should be displayed in HTML wrap
     * @param null $type
     * @return array
     */
    public function getFlashesWrapped($type = null)
    {
        $messages = $this->getFlashes($type);

        $wrappedMessages = [];

        if ($type && isset($messages[$type])) {

            foreach ($messages[$type] as $message) {
                $wrappedMessages[$type][] = $this->wrapMessage($type, $message);
            }

        } elseif (count($messages) > 0) {

            foreach ($messages as $type => $array) {
                foreach ($array as $message) {
                    $wrappedMessages[$type][] = $this->wrapMessage($type, $message);
                }
            }
        }

        return $wrappedMessages;
    }

    public function doesMessageExist($type)
    {
//        print_r($this->sessions->getFromSession('messages'));
        return $this->sessions->getFromSession('messages.' . $type) ? true : false;
    }

    /**
     * Unset given messages from session
     * @param array $messages
     */
    private function unsetFromSession($messages)
    {
        foreach ($messages as $type => $arr) {
            foreach ($arr as $index => $val) {
                $this->sessions->delFromSession('messages.' . $type . '.' . $index);
            }
        }
    }

    /**
     * Wrap message with HTML wrap. Markdown link support.
     * @param $type
     * @param $message
     * @return mixed
     */
    private function wrapMessage($type, $message)
    {
        $wrappedMessage = str_replace('{{ msg }}', $message, $this->wraps[$type]);
        return $wrappedMessage;
    }
}