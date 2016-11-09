<?php
namespace Webiik;

class Flash
{
    /**
     * @var Arr
     */
    private $arr;

    /**
     * @var Sessions
     */
    private $sessions;

    /** @var array All 'now' messages */
    private $messages = [];

    /** @var array All 'next' messages */
    private $messagesNext = [];

    private $wraps = [];

    /**
     * Flash constructor.
     * @param $sessions Sessions
     * @param $arr Arr
     */
    public function __construct(Sessions $sessions, Arr $arr)
    {
        $this->sessions = $sessions;
        $this->arr = $arr;
    }

    /**
     * Set HTML wrap
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
        $this->sessions->addToSession('messages.' . $type, $message);
    }

    /**
     * Get all messages that should be displayed
     * @param null $type
     * @return array
     */
    public function getFlashes($type = null)
    {
        $messages = $this->sessions->getFromSession('messages');

        if ($messages) {

            $messages = $this->arr->diffMulti($messages, $this->messagesNext);
            $this->unsetFromSession($messages);
            $messages = array_merge_recursive($messages, $this->messages);

        } else {

            $messages = $this->messages;
        }

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

    /**
     * Unset all displayed 'next' messages from session
     * @param $array
     * @param string $type
     */
    private function unsetFromSession($array, $type = '')
    {
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                if (empty($val)) {
                    $this->sessions->delFromSession('messages.' . $key);
                } else {
                    $this->unsetFromSession($val, $key);

                }
            } else {
                $this->sessions->delFromSession('messages.' . $type . $key);

                if (empty($this->sessions->getFromSession('messages.' . $type))) {
                    $this->sessions->delFromSession('messages.' . $type);
                }
            }
            if (empty($this->sessions->getFromSession('messages'))) {
                $this->sessions->delFromSession('messages');
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