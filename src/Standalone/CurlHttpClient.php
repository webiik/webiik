<?php
namespace Webiik;

/**
 * Class CurlHttpClient
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class CurlHttpClient
{
    /**
     * Options cheat sheet
     *
     * You can add any PHP supported CURL option to options array:
     * @link http://php.net/manual/en/function.curl-setopt.php
     *
     * Or use one of helper options below:
     * Get response headers
     * 'getHeaders' => false,
     *
     * Get response body
     * 'getBody' => true,
     *
     * Set response timeout
     * 'timeout' => 300,
     *
     * Set encoding, equivalent of: curl --compressed
     * 'encoding' => '',
     *
     * Set request http headers
     * 'httpHeaders' => [],
     *
     * Set request post data
     * 'postData' => [],
     *
     * Allow request redirection after hitting the target url
     * 'followLocation' => false,
     * 'maxRedirects' => -1,
     *
     * Set request user agent string
     * 'agent' => false,
     *
     * Set request referrer header
     * 'referrer' => false,
     *
     * Send request via proxy
     * 'proxy' => false,
     * 'proxyPort' => false,
     * 'proxyAuth' => false,
     *
     * Set cookies
     * 'cookie' => false,
     * 'cookieFromFile' => false,
     * 'cookieToFile' => false,
     *
     * Verify SSL at target url
     * 'verifySSL' => true,
     *
     * Note:
     * If you will use empty options then default CURL values will be set.
     * Some options are automatically set to reflect required method.
     */

    /**
     * @param $url
     * @param array $options
     * @return array|string
     */
    public function get($url, $options = [])
    {
        $curl = curl_init($url);
        $options = $this->prepareOptions($options);
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @param array|string|bool $postData
     * @return array|string
     */
    public function post($url, $options = [], $postData = false)
    {
        $curl = curl_init($url);
        $postData = $postData ? $postData : true;
        $options = $this->prepareOptions($options, $postData);
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @param array|string|bool $postData
     * @return array|string
     */
    public function put($url, $options = [], $postData = false)
    {
        $curl = curl_init($url);
        $postData = $postData ? $postData : true;
        $options = $this->prepareOptions($options, $postData);
        $options['curl'][CURLOPT_CUSTOMREQUEST] = 'PUT';
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @param array|string|bool $postData
     * @return array|string
     */
    public function delete($url, $options = [], $postData = false)
    {
        $curl = curl_init($url);
        $postData = $postData ? $postData : true;
        $options = $this->prepareOptions($options, $postData);
        $options['curl'][CURLOPT_CUSTOMREQUEST] = 'DELETE';
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @return array|string
     */
    public function head($url, $options = [])
    {
        $curl = curl_init($url);
        $options = $this->prepareOptions($options);
        $options['curl'][CURLOPT_CUSTOMREQUEST] = 'HEAD';
        $options['curl'][CURLOPT_NOBODY] = 1;
        $options['curl'][CURLOPT_HEADER] = 1;
        $options['withBody'] = false;
        $options['withHeader'] = true;
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @return array|string
     */
    public function options($url, $options = [])
    {
        $curl = curl_init($url);
        $options = $this->prepareOptions($options);
        $options['curl'][CURLOPT_CUSTOMREQUEST] = 'OPTIONS';
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * @param $url
     * @param array $options
     * @param $postData
     * @return array|string
     */
    public function patch($url, $options = [], $postData)
    {
        $curl = curl_init($url);
        $postData = $postData ? $postData : true;
        $options = $this->prepareOptions($options, $postData);
        $options['curl'][CURLOPT_CUSTOMREQUEST] = 'PATCH';
        curl_setopt_array($curl, $options['curl']);
        return $this->send($curl, $options['withHeader'], $options['withBody']);
    }

    /**
     * Search header value by header name (not case sensitive) in given headers
     * Return header value on success, otherwise false
     * @param $headerName
     * @param array $headers
     * @return bool
     */
    public function findHeader($headerName, $headers = [])
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
     * Download remote file by chunks using curl
     * @param string $url
     * @param string $filePath
     * @param array $options
     * @param int $chunkSize
     * @return array
     */
    public function download($url, $filePath, $options = [], $chunkSize = 8096)
    {
        $opt = [
            CURLOPT_BINARYTRANSFER => 1,
            CURLOPT_BUFFERSIZE => $chunkSize,
            CURLOPT_WRITEFUNCTION => $this->getWriteFunction($filePath),
            CURLOPT_HEADER => 0,
        ];

        // Merge options
        foreach ($options as $key => $val){
            $opt[$key] = $val;
        }

        $res = $this->get($url, $opt);

        // If request fails
        if (count($res['err']) > 0) {
            @unlink($filePath);
        }

        return $res;
    }

    /**
     * Prepare array of CURL options
     * @param array $options
     * @param bool $postData
     * @return array
     */
    private function prepareOptions($options = [], $postData = false)
    {
        // Shared options for all requests
        $opt = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => 1,
        ];

        // Set encoding
        if (isset($options['encoding'])) {
            $opt[CURLOPT_ENCODING] = $options['encoding'];
            unset($options['encoding']);
        }

        // VerifySSL
        if (isset($options['verifySSL']) && is_bool($options['verifySSL'])) {
            $opt[CURLOPT_SSL_VERIFYHOST] = $options['verifySSL'];
            $opt[CURLOPT_SSL_VERIFYPEER] = $options['verifySSL'];
            unset($options['verifySSL']);
        }

        // Set request via proxy
        if (isset($options['proxy']) && is_string($options['proxy'])) {
            $opt[CURLOPT_PROXY] = $options['proxy'];
            unset($options['proxy']);
        }

        // Set proxy port
        if (isset($options['proxyPort']) && is_numeric($options['proxyPort'])) {
            $opt[CURLOPT_PROXYPORT] = $options['proxyPort'];
            unset($options['proxyPort']);
        }

        // Set proxy auth credentials
        if (isset($options['proxyAuth']) && is_string($options['proxyAuth'])) {
            $opt[CURLOPT_PROXYUSERPWD] = $options['proxyAuth'];
            unset($options['proxyAuth']);
        }

        // Request timeout
        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            $opt[CURLOPT_CONNECTTIMEOUT] = $options['timeout'];
            $opt[CURLOPT_TIMEOUT] = $options['timeout'];
            unset($options['timeout']);
        }

        // Get response without body
        // Default response is with body
        $data['withBody'] = true;
        $opt[CURLOPT_NOBODY] = 0;

        if (
            (isset($options['getBody']) && !$options['getBody']) ||
            (isset($options[CURLOPT_NOBODY]) && $options[CURLOPT_NOBODY])
        ) {
            $data['withBody'] = false;
            $opt[CURLOPT_NOBODY] = 1;
        }

        if (isset($options['getBody'])) {
            unset($options['getBody']);
        }

        // Get response with headers
        // Default response is with headers
        $data['withHeader'] = true;
        $opt[CURLOPT_HEADER] = 1;

        if (
            (isset($options['getHeaders']) && !$options['getHeaders']) ||
            (isset($options[CURLOPT_HEADER]) && !$options[CURLOPT_HEADER])
        ) {
            $data['withHeader'] = false;
            $opt[CURLOPT_HEADER] = 0;
        }

        if (isset($options['getHeaders'])) {
            unset($options['getHeaders']);
        }

        // Allow request redirects
        if (isset($options['followLocation']) && $options['followLocation']) {
            $opt[CURLOPT_FOLLOWLOCATION] = 1;
            unset($options['followLocation']);
        }

        // Set max count of redirects
        if (isset($options['maxRedirects']) && is_numeric($options['maxRedirects'])) {
            $opt[CURLOPT_MAXREDIRS] = $options['maxRedirects'];
            unset($options['maxRedirects']);
        }

        // Set http headers
        if (isset($options['httpHeaders']) && is_array($options['httpHeaders'])) {
            $opt[CURLOPT_HTTPHEADER] = $options['httpHeaders'];
            unset($options['httpHeaders']);
        }

        // Set cookie using http headers
        if (isset($options['cookie']) && is_array($options['cookie'])) {

            $cookies = '';

            foreach ($options['cookie'] as $key => $val) {
                $cookies .= ';' . $key . '=' . $val;
                $cookies = ltrim($cookies, ';');
            }

            $opt[CURLOPT_HTTPHEADER][] = 'Cookie: ' . $cookies;
            unset($options['cookie']);
        }

        // Set cookie from file
        if (isset($options['cookieFromFile']) && file_exists($options['cookieFromFile'])) {
            $opt[CURLOPT_COOKIEFILE] = $options['cookieFromFile'];
            unset($options['cookieFromFile']);
        }

        // Store all internal cookies after curl_close to file
        if (isset($options['cookieToFile']) && is_string($options['cookieToFile'])) {
            $opt[CURLOPT_COOKIEJAR] = $options['cookieToFile'];
            unset($options['cookieToFile']);
        }

        // Set post data
        // Set post data
        if ($postData) {
            $opt[CURLOPT_POST] = 1;
            if (is_array($postData)) {
                if (count($postData) > 0) {
                    $opt[CURLOPT_POSTFIELDS] = http_build_query($postData);
                }
            } else if ($postData !== true) {
                $opt[CURLOPT_POSTFIELDS] = $postData;
            }
        }

        // Set user agent
        if (isset($options['agent']) && is_string($options['agent'])) {
            $opt[CURLOPT_USERAGENT] = $options['agent'];
            unset($options['agent']);
        }

        // Set referrer
        if (isset($options['referrer']) && is_string($options['referrer'])) {
            $opt[CURLOPT_REFERER] = $options['referrer'];
            unset($options['referrer']);
        }

        // Merge options
        foreach ($options as $key => $val) {
            $opt[$key] = $val;
        }

        $data['curl'] = $opt;

        return $data;
    }

    /**
     * Return array with 'header', 'body', 'err' and 'info'
     * @param $curl
     * @param bool $withHeader
     * @param bool $withBody
     * @return array
     */
    private function send($curl, $withHeader, $withBody)
    {
        $response = curl_exec($curl);

        $data = [];
        $data['header'] = null;
        $data['body'] = null;
        $data['err'] = curl_error($curl);
        $data['info'] = curl_getinfo($curl);
        $data['status'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }
        }
        $data['cookies'] = $cookies;

        if ($withHeader && !$withBody) {
            $data['header'] = $this->processCurlHeaders($response);
        }

        if (!$withHeader && $withBody) {
            $data['body'] = $response;
        }

        if ($withHeader && $withBody) {
            $headerLength = $data['info']['header_size'];
            $data['header'] = $this->processCurlHeaders(substr($response, 0, $headerLength));
            $data['body'] = substr($response, $headerLength);
        }

        curl_close($curl);

        return $data;
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

        if (isset($httpHeaders['Http'])) {
            parse_str($httpHeaders['Http'], $headers);
            unset($httpHeaders['Http']);
            $httpHeaders = array_merge($httpHeaders, $headers);
        }

        return $httpHeaders;
    }

    /**
     * Return Curl write function
     * @param $filePath
     * @return \Closure
     */
    private function getWriteFunction($filePath)
    {
        return function ($curl, $chunk) use ($filePath) {
            if (!file_put_contents($filePath, $chunk, FILE_APPEND)) {
                return false;
            }
            ob_flush();
            flush();
            return strlen($chunk);
        };
    }
}