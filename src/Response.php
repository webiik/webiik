<?php
namespace Webiik;

class Response
{
     /**
     * @var array HTTP 1.1 response codes and messages
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    private $headers = [
        // Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        // Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        // Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        // Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        // Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
    ];

    /**
     * Content types
     * @link http://snipplr.com/view/1937/array-of-mime-types/
     * @var array
     */
    private $contentTypes = [
        'json' => 'Content-Type: application/json',
        'atom' => 'Content-Type: application/atom+xml',
        'rss' => 'Content-Type: application/rss+xml; charset=ISO-8859-1',
        'pdf' => 'Content-Type: application/pdf',
        'html' => 'Content-Type: text/html',
        'xml' => 'Content-Type: text/xml',
        'txt' => 'Content-Type: text/plain',
        'css' => 'Content-Type: text/css',
        'js' => 'Content-Type: text/javascript',
        'jpeg' => 'Content-Type: image/jpeg',
        'png' => 'Content-Type: image/png',
    ];

    private $contentType = false;

    /**
     * Set one HTTP/1.1 headers by their codes
     * @param int $headerCode
     * @throws \Exception
     */
    public function setHeader($headerCode)
    {
        if (array_key_exists($headerCode, $this->headers)) {
            header('HTTP/1.1 ' . strtr($headerCode, $this->headers));
        } else {
            throw new \Exception('{' . $headerCode . '} is invalid http header key.');
        }
    }

    /**
     * Add named content types. It uses array_merge so keys can be overwritten.
     * @param array $keyValueArray
     */
    public function addContentTypes($keyValueArray)
    {
        $this->contentTypes = array_merge($this->contentTypes, $keyValueArray);
    }

    /**
     * Set content type header by its array key
     * @param string $fileExtension
     * @throws \Exception
     */
    public function setContentType($fileExtension)
    {
        if (array_key_exists($fileExtension, $this->contentTypes)) {
            header(strtr($fileExtension, $this->contentTypes));
            $this->contentType = $fileExtension;
        } else {
            throw new \Exception('{' . $fileExtension . '} unsupported content type.');
        }
    }

    /**
     * Download document content to file
     * @param $fileName
     * @throws \Exception
     */
    public function download($fileName)
    {
        if($this->contentType) {
            header('Content-Disposition: attachment;filename=' . $fileName . '.' . $this->contentType);
            header('Content-Transfer-Encoding: binary');
        } else {
            throw new \Exception('{setContentType} before download.');
        }
    }

    /**
     * Redirect to the specific path
     * @param string $path
     * @param int $headerCode
     * @throws \Exception
     */
    public function redirect($path, $headerCode)
    {
        if ($headerCode > 299 && $headerCode < 308) {
            header('HTTP/1.1 ' . strtr($headerCode, $this->headers));
            header('Location:' . $path);
            exit();
        }
        throw new \Exception('Redirect header must be 300-307, but {' . $headerCode . '} was given.');
    }

    /**
     * Set Cache-Control header
     * @param $seconds
     * @param string $behaviour
     */
    public function setCacheControl($seconds, $behaviour = 'private')
    {
        $behaviour ? $behaviour = ', '.$behaviour : $behaviour = '';
        header('Cache-Control: max-age:' . $seconds . $behaviour);
    }

    /**
     * Set Expires header
     * @param $dateTime
     */
    public function setExpires($dateTime)
    {
        $dateTime = gmdate('D, d M Y H:i:s ', strtotime($dateTime)) . 'GMT';
        header('Expires: ' . $dateTime);
    }

    /**
     * Set Last-Modified header, if exists same Last-Modified header set 304 and exit
     * @param $dateTime
     * @throws \Exception
     */
    public function setLastModified($dateTime)
    {
        $dateTime = gmdate('D, d M Y H:i:s ', strtotime($dateTime)) . 'GMT';
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;

        if($ifModifiedSince && $ifModifiedSince == $dateTime){
            // Cached
            $this->setHeader(304);
            exit;
        }

        header('Last-Modified: ' . $dateTime);
    }

    /**
     * Set ETag header, if exists same ETag header set 304 and exit
     * @param $etag
     * @throws \Exception
     */
    public function setEtag($etag)
    {
        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;

        if ($ifNoneMatch && $ifNoneMatch == $etag) {
            // Cached
            $this->setHeader(304);
            exit;
        }

        header('ETag: ' . $etag);
    }
}