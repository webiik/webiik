<?php
namespace Webiik;

 // Clean flash messaging with custom wrappers.
class Flash
{
    /** @var array All 'now' messages */
    private $messages = [];

    /** @var array All 'next' messages */
    private $messagesNext = [];

    private $wraps = [];

    /**
     * Set message into 'now' messages
     *
     * @param $type
     * @param $message
     */
    public function setFlashNow($type, $message)
    {
        $this->messages[$type][] = $message;
    }

    /**
     * Set message into 'next' messages and into session
     *
     * @param $type
     * @param $message
     */
    public function setFlashNext($type, $message)
    {
        $_SESSION['messages'][$type][] = $message;
        $this->messagesNext[$type][] = $message;
    }

    /**
     * Get all messages that should be displayed
     *
     * @param null $type
     * @return array
     */
    public function getFlash($type = null)
    {
        if(isset($_SESSION['messages'])) {
            $messages = $this->arrayDiffMulti($_SESSION['messages'], $this->messagesNext);
            $this->unsetFromSession($messages);
            $messages = array_merge_recursive($messages, $this->messages);

        } else {

            $messages = $this->messages;
        }

        if($type){
            return [$type => $messages[$type]];
        }

        return($messages);
    }

    /**
     * Unset all displayed 'next' messages from session
     *
     * @param $array
     * @param string $type
     */
    private function unsetFromSession($array, $type = '')
    {
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                if (empty($val)) {
                    unset($_SESSION['messages'][$key]);
                } else {
                    $this->unsetFromSession($val, $key);

                }
            } else {
                unset($_SESSION['messages'][$type][$key]);
                if(empty($_SESSION['messages'][$type])){
                    unset($_SESSION['messages'][$type]);
                }
            }
            if(empty($_SESSION['messages'])){
                unset($_SESSION['messages']);
            }
        }
    }

    /**
     * Get all messages that should be displayed in HTML wrap
     *
     * @param null $type
     * @return array
     */
    public function getFlashWrapped($type = null)
    {
        $messages = $this->getFlash($type);

        $wrappedMessages = [];

        if ($type) {
            foreach ($messages[$type] as $message) {
                $wrappedMessages[$type][] = $this->wrapMessage($type, $message);
            }

        } else {
            foreach ($messages as $type => $array) {
                foreach ($array as $message) {
                    $wrappedMessages[$type][] = $this->wrapMessage($type, $message);
                }
            }
        }
        return $wrappedMessages;
    }

    /**
     * Set HTML wrap
     *
     * @param $type
     * @param $wrap
     */
    public function setWrap($type, $wrap)
    {
        $this->wraps = array_merge($this->wraps, array($type => $wrap));
    }

    /**
     * Multidimensional array_diff
     *
     * @param $array1
     * @param $array2
     * @return array
     */
    private function arrayDiffMulti($array1, $array2)
    {
        $result = array();
        foreach ($array1 as $key => $val) {
            if (array_key_exists($key, $array2)) {
                if (is_array($val) && is_array($array2[$key]) && !empty($val)) {
                    $temRes = $this->arrayDiffMulti($val, $array2[$key]);
                    if(count($temRes) > 0){
                        $result[$key] = $temRes;
                    }
                }
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    /**
     * Wrap message with HTML wrap. Markdown link support.
     *
     * @param $type
     * @param $message
     * @return mixed
     */
    // Todo: Rewrite it to wrap whatever message, it means remove str_replace with something more universal
    private function wrapMessage($type, $message)
    {
        $wrappedMessage = str_replace('%msg%', $message, $this->wraps[$type]);
        return $wrappedMessage;
    }
}