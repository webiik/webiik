<?php
namespace Webiik;

/**
 * Class Remote
 * Manipulate remote files with ease
 *
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Remote
{
    private $timeout = 1;
    private $agent = false;
    private $referrer = false;
    private $buffer = 8096;

    /**
     * Set user agent for curl operation, default behaviour does not expose user agent
     * @param $agent
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;
    }

    /**
     * Set referrer for curl operation, default behaviour does not expose referrer
     * @param $referrer
     */
    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;
    }

    /**
     * Set timeout for establish curl connection, default value is 1s
     * @param $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Set size of buffer for chunks, default is 8096
     * @param $buffer
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * Return http headers of remote url on success, otherwise false
     * @param $url
     * @param bool $followLocation
     * @return array|bool
     */
    public function getHeaders($url, $followLocation = false)
    {
        if (!$this->isUrl($url)) return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if ($followLocation) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        if ($this->agent) curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($this->referrer) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referrer);
        } else {
            curl_setopt($ch, CURLOPT_REFERER, $url);
        }

        if (!$headers = curl_exec($ch)) {
            $res = false;
        } else {
            $res = $this->processCurlHeaders($headers);
        }

        curl_close($ch);

        return $res;
    }

    /**
     * Return content of remote url on success, otherwise false
     * @param $url
     * @param bool $followLocation
     * @return bool|mixed
     */
    public function getContent($url, $followLocation = false)
    {
        if (!$this->isUrl($url)) return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($followLocation) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        if ($this->agent) curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($this->referrer) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referrer);
        } else {
            curl_setopt($ch, CURLOPT_REFERER, $url);
        }

        if (!$content = curl_exec($ch)) {
            $res = false;
        } else {
            $res = $content;
        }

        curl_close($ch);

        return $res;
    }

    /**
     * Download remote file by chunks to local file
     * Return true on success, otherwise false
     */
    public function downloadFile($url, $file, $followLocation = false)
    {
        if (!$this->getFile($url)) {
            return false;
        }

        $curlWrite = function ($ch, $chunk) use ($file) {
            if (!file_put_contents($file, $chunk, FILE_APPEND)) {
                return false;
            }
            ob_flush();
            flush();
            return strlen($chunk);
        };

        // Set up curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, $this->buffer);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, $curlWrite);
        if ($followLocation) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($this->agent) curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($this->referrer) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referrer);
        } else {
            curl_setopt($ch, CURLOPT_REFERER, $url);
        }
        $cr = curl_exec($ch);
        curl_close($ch);

        // If curl fails
        if (!$cr) {
            @unlink($file);
            return false;
        }

        return true;
    }

    /**
     * Search header value by header name (not case sensitive) in given headers
     * Return header value on success, otherwise false
     * @param $headerName
     * @param array $headers
     * @return bool
     */
    public function searchHeader($headerName, $headers = [])
    {
        if (isset($headers[$headerName])) {
            return $headers[$headerName];
        }

        if (isset($headers[strtolower($headerName)])) {
            return $headers[strtolower($headerName)];
        }

        if (isset($headers[ucwords(strtolower($headerName), '-_')])) {
            return $headers[ucwords(strtolower($headerName), '-_')];
        }

        if (isset($headers[strtoupper($headerName)])) {
            return $headers[strtoupper($headerName)];
        }

        return false;
    }

    /**
     * Return path without filename and trailing slash on success, otherwise false.
     * @param $url
     * @return string|bool
     */
    public function getPath($url)
    {
        if (!$this->isUrl($url)) return false;

        $urlParts = parse_url($url);

        $url = isset($urlParts['path']) ? $urlParts['path'] : false;

        $uri = $urlParts['scheme'] . '://' . $urlParts['host'];

        // Process path
        if ($url) {
            $url = pathinfo($url);

            if (isset($url['extension'])) {
                $url = $url['dirname'];
            } else {
                $url = $url['basename'] ? $url['dirname'] . '/' . $url['basename'] : $url['dirname'];
            }
        }

        return rtrim($uri . $url, '/');
    }

    /**
     * Return file name with extension on success, otherwise false
     * @param $url
     * @return string|bool
     */
    public function getFile($url)
    {
        if (!$this->isUrl($url)) return false;

        $urlParts = parse_url($url);

        if (isset($urlParts['path'])) {
            preg_match('/\/([^\/]*\.\w{1,}$)/', $urlParts['path'], $match);
        }

        return isset($match[1]) ? $match[1] : false;
    }

    /**
     * Check if given string is valid URL
     * Return true on success, otherwise false
     * @param $str
     * @return bool
     */
    public function isUrl($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * Create array from curl http headers
     * @param $headers
     * @return array
     */
    private function processCurlHeaders($headers)
    {
        $httpHeaders = [];

        $headers = explode("\n", $headers);
        foreach ($headers as $header) {

            $delimeterPos = strpos($header, ':');
            $header = trim($header);

            if ($delimeterPos) {
                $httpHeaders[substr($header, 0, $delimeterPos)] = substr($header, $delimeterPos + 2, strlen($header));
            } else if ($header) {
                $httpHeaders['Http'] = $header;
            }
        }

        return $httpHeaders;
    }
}