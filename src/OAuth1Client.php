<?php
namespace Webiik;

class OAuth1Client
{
    /**
     * @var Token
     */
    private $token;

    /**
     * @var Http
     */
    private $http;

    private $oauth_callback_url = '';

    private $oauth_request_token_url = '';

    private $oauth_access_token_url = '';

    private $oauth_authorize_url = '';

    private $oauth_consumer_secret = '';

    private $oauth_consumer_key = '';

    private $oauth_signature_method = 'HMAC-SHA1';

    private $oauth_signature_secret = '';

    private $oauth_version = '1.0';

    private $data = [];

    private $headers = [];

    /**
     * OAuthOneClient constructor.
     * @param Http $http
     * @param Token $token
     */
    public function __construct(Http $http, Token $token)
    {
        $this->token = $token;
        $this->http = $http;
    }

    /**
     * @param string $secret
     */
    public function setConsumerSecret($secret)
    {
        $this->oauth_consumer_secret = $secret;
    }

    /**
     * @param string $key
     */
    public function setConsumerKey($key)
    {
        $this->oauth_consumer_key = $key;
    }

    /**
     * @param string $url
     */
    public function setCallbackUrl($url)
    {
        $this->oauth_callback_url = $url;
    }

    /**
     * @param string $url
     */
    public function setReqestTokenUrl($url)
    {
        $this->oauth_request_token_url = $url;
    }

    /**
     * @param string $url
     */
    public function setAccessTokenUrl($url)
    {
        $this->oauth_access_token_url = $url;
    }

    /**
     * @param string $url
     */
    public function setAuthorizeUrl($url)
    {
        $this->oauth_authorize_url = $url;
    }

    /**
     * @param string $secret
     */
    public function setSignatureSecret($secret)
    {
        $this->oauth_signature_secret = $secret;
    }

    /**
     * On success return array with 'oauth_token' and other data if provided
     * On error return array with response 'header', 'body', 'err' and 'info'
     * @return array
     */
    public function getRequestTokenData()
    {
        $this->prepareData();
        $this->prepareSignature($this->oauth_request_token_url);
        $this->prepareHeaders();

        $res = $this->http->post($this->oauth_request_token_url, ['httpHeaders' => $this->headers], []);

        if ($this->isResOk($res)) {
            parse_str($res['body'], $body);
            return $body;
        }

        return $res;
    }

    /**
     * Return authorize URL with filled request token
     * @param string $requestToken
     * @return string
     */
    public function getLoginUrl($requestToken)
    {
        return $this->oauth_authorize_url . '?oauth_token=' . urlencode($requestToken);
    }

    /**
     * Redirect user to authorize URL with filled request token
     * @param $requestToken
     */
    public function redirectToLoginUrl($requestToken)
    {
        header('HTTP/1.1 302 Found');
        header('Location: ' . $this->getLoginUrl($requestToken));
        exit;
    }

    /**
     * On success return array with 'oauth_token' and other data if provided
     * On error return array with response 'header', 'body', 'err' and 'info'
     * @return array
     */
    public function getAccessTokenData()
    {
        $oauth_token = isset($_GET['oauth_token']) ? $_GET['oauth_token'] : '';
        $oauth_verifier = isset($_GET['oauth_verifier']) ? $_GET['oauth_verifier'] : '';

        $this->prepareData($oauth_token);
        $this->prepareSignature($this->oauth_access_token_url);
        $this->prepareHeaders($oauth_verifier);

        $postData = ['oauth_verifier' => $oauth_verifier];

        $res = $this->http->post($this->oauth_access_token_url, ['httpHeaders' => $this->headers], $postData);

        if ($this->isResOk($res)) {
            parse_str($res['body'], $body);
            return $body;
        }

        return $res;
    }

    /**
     * Prepare array data. We'll generate request http headers from it.
     * @param bool|string $oauth_token
     */
    private function prepareData($oauth_token = false)
    {
        $this->data = [
            'oauth_callback' => $this->oauth_callback_url,
            'oauth_consumer_key' => $this->oauth_consumer_key,
            'oauth_signature_method' => $this->oauth_signature_method,
            'oauth_timestamp' => time(),
            'oauth_nonce' => $this->token->generate(3),
            'oauth_version' => $this->oauth_version,
        ];

        if ($oauth_token) {
            $this->data['oauth_token'] = $oauth_token;
        }

        ksort($this->data);
    }

    /**
     * Generate OAuth v1 signature and add it to array data
     * @param string $url
     */
    private function prepareSignature($url)
    {
        $signData = 'POST&' . urlencode($url) . '&' . urlencode(http_build_query($this->data));
        $signKey = urlencode($this->oauth_consumer_secret) . '&' . urlencode($this->oauth_signature_secret);
        $this->data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));
    }

    /**
     * Prepare request http headers from array data
     * @param string|bool $oauth_verifier
     */
    private function prepareHeaders($oauth_verifier = false)
    {
        $this->headers = [];

        foreach ($this->data as $key => $value) {
            $this->headers[] = urlencode($key) . '="' . urlencode($value) . '"';
        }

        $this->headers = ['Authorization: OAuth ' . implode(', ', $this->headers)];

        if ($oauth_verifier) {
            $this->headers[] = 'Content-Length: ' . strlen('oauth_verifier=' . urlencode($oauth_verifier));
            $this->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
    }

    /**
     * Check http status of response and return true if status is 200, otherwise false
     * @param array $res
     * @return bool
     */
    private function isResOk($res)
    {
        return $res['status'] == 200 ? true : false;
    }
}