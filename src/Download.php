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
class Download
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

        $res = $this->http->get($url, $opt);

        // If request fails
        if (count($res['err']) > 0) {
            @unlink($filePath);
        }

        return $res;
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