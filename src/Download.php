<?php
namespace Webiik;

/**
 * Class Download
 * Download remote file by chunks using curl
 *
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Remote
{
    /**
     * @var Http
     */
    private $http;

    /**
     * Remote constructor.
     * @param Http $http
     */
    public function __construct(Http $http)
    {
        $this->http = $http;
    }

    /**
     * Download remote file by chunks using curl
     * @param $url
     * @param $file
     * @param array $options
     * @param int $chunkSize
     * @return bool
     */
    public function download($url, $file, $options = [], $chunkSize = 8096)
    {
        $curlWrite = function ($ch, $chunk) use ($file) {
            if (!file_put_contents($file, $chunk, FILE_APPEND)) {
                return false;
            }
            ob_flush();
            flush();
            return strlen($chunk);
        };

        $options = array_merge($options, [
            CURLOPT_BINARYTRANSFER => 1,
            CURLOPT_BUFFERSIZE => $chunkSize,
            CURLOPT_WRITEFUNCTION => $curlWrite,
        ]);

        $res = $this->http->get($url, $options);

        // If curl fails
        if (is_string($res)) {
            @unlink($file);
            return false;
        }

        return true;

//        if (!$this->getFile($url)) {
//            return false;
//        }
//
//
//
//        // Set up curl
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
//        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
//        curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->buffer);
//        curl_setopt($ch, CURLOPT_WRITEFUNCTION, $curlWrite);
//
//
//        if ($followLocation) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        if ($this->agent) curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
//        if ($this->referrer) {
//            curl_setopt($ch, CURLOPT_REFERER, $this->referrer);
//        } else {
//            curl_setopt($ch, CURLOPT_REFERER, $url);
//        }
//        $cr = curl_exec($ch);
//        curl_close($ch);
//
//        // If curl fails
//        if (!$cr) {
//            @unlink($file);
//            return false;
//        }
//
//        return true;
    }
}