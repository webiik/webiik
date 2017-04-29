<?php
namespace Webiik;

/**
 * Class OAuth2Client
 * @package Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class OAuth2Client
{
    /**
     * @var CurlHttpClient
     */
    private $http;

    private $oauth_redirect_uri = '';

    private $oauth_authorize_url = '';

    private $oauth_access_token_url = '';

    private $oauth_validate_token_url = '';

    private $oauth_client_secret = '';

    private $oauth_client_id = '';

    /**
     * OAuth2Client constructor.
     * @param CurlHttpClient $http
     */
    public function __construct(CurlHttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * @param string $secret
     */
    public function setClientSecret($secret)
    {
        $this->oauth_client_secret = $secret;
    }

    /**
     * @param string $id
     */
    public function setClientId($id)
    {
        $this->oauth_client_id = $id;
    }

    /**
     * @param string $url
     */
    public function setRedirectUri($url)
    {
        $this->oauth_redirect_uri = $url;
    }

    /**
     * @param string $url
     */
    public function setAuthorizeUrl($url)
    {
        $this->oauth_authorize_url = $url;
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
    public function setValidateTokenUrl($url)
    {
        $this->oauth_validate_token_url = $url;
    }

    /**
     * Return authorization URL with all required parameters
     * @param array $scope
     * @param string $responseType
     * @param bool $state
     * @return string
     */
    public function getLoginUrl($scope = [], $responseType = 'code', $state = false)
    {
        $data = [
            'client_id' => $this->oauth_client_id,
            'redirect_uri' => $this->oauth_redirect_uri,
            'response_type' => $responseType,
            'scope' => implode(' ', $scope),
        ];

        if ($state) {
            $data['state'] = $state;
        }

        return $this->oauth_authorize_url . '?' . http_build_query($data);
    }

    /**
     * @param $code
     * @param $method
     * @return array
     */
    public function getAccessTokenByCode($code, $method = 'POST')
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
        ];

        return $this->getAccessToken($data, [], $method);
    }

    /**
     * @param $username
     * @param $password
     * @param $method
     * @return array
     */
    public function getAccessTokenByPassword($username, $password, $method = 'POST')
    {
        $data = [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
        ];

        $options = [
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_USERPWD => $username . ':' . $password,
        ];

        return $this->getAccessToken($data, $options, $method);
    }

    /**
     * @param $username
     * @param $password
     * @param $method
     * @return array
     */
    public function getAccessTokenByCredentials($username, $password, $method = 'POST')
    {
        $data = [
            'grant_type' => 'client_credentials',
        ];

        $options = [
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_USERPWD => $username . ':' . $password,
        ];

        return $this->getAccessToken($data, $options, $method);
    }

    /**
     * @param $refreshToken
     * @param $method
     * @return array
     */
    public function getAccessTokenByRefreshToken($refreshToken, $method = 'POST')
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        return $this->getAccessToken($data, [], $method);
    }

    /**
     * On success return array with various keys...
     * On error return array with response 'header', 'body', 'err' and 'info'
     * @param $inputToken
     * @param $accessToken
     * @param $method
     * @return array
     */
    public function getTokenInfo($inputToken, $accessToken, $method = 'POST')
    {
        $data = [
            'input_token' => $inputToken,
            'access_token' => $accessToken,
        ];

        if ($method == 'POST') {
            $res = $this->http->post($this->oauth_validate_token_url, [], $data);
        } else {
            $res = $this->http->get($this->oauth_validate_token_url . '?' . http_build_query($data), []);
        }

        if ($this->isResOk($res)) {
            return json_decode($res['body'], true);
        }

        return $res;
    }

    /**
     * On success return array with 'access_token', 'token_type', 'expires_in' and other data if provided
     * On error return array with response 'header', 'body', 'err' and 'info'
     * @param array $grantTypeData
     * @param array $options
     * @param string $method
     * @return array
     */
    private function getAccessToken($grantTypeData = [], $options = [], $method = 'POST')
    {
        $data = [
            'client_id' => $this->oauth_client_id,
            'client_secret' => $this->oauth_client_secret,
            'redirect_uri' => $this->oauth_redirect_uri,
        ];

        $data = array_merge($data, $grantTypeData);

        if($method == 'POST'){
            $res = $this->http->post($this->oauth_access_token_url, $options, $data);
        } else {
            $res = $this->http->get($this->oauth_access_token_url . '?' . http_build_query($data), $options);
        }

        if ($this->isResOk($res)) {
            return json_decode($res['body'], true);
        }

        return $res;
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