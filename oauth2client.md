# OAuth 2 client
Tiny OAuth 2 client for your web app. Only one class and 130 lines of code you will easily understand. Use it as it is or rewrite it for your needs.

## Supported providers
- every that uses OAuth 2 (FB, Google,...)  

## Installation
OAuth2 client is part of [Webiik platform](readme.md). Before using OAuth2 in your project, install it with the following command:
```bash
composer require mihi/webiik
```

## How to use it?

#### Facebook login example
```php
// Instatiate required classes
$http = new Http();
$oauth = new OAuth2Client($http);

// Setup

// Your callback URL after authorization
$oauth->setRedirectUri('https://localhost/login/');

// API end points
$oauth->setAuthorizeUrl('https://www.facebook.com/v2.8/dialog/oauth');
$oauth->setAccessTokenUrl('https://graph.facebook.com/v2.8/oauth/access_token');
$oauth->setValidateTokenUrl('https://graph.facebook.com/debug_token');

// API credentials (create yours at https://developers.facebook.com/apps/)
$oauth->setClientId('xxx');
$oauth->setClientSecret('xxx');

// Make API calls

// Define scope
$scope = [
    'email',
];

// Create login URL with specified scope and grand type
$url = $oauth->getLoginUrl(['email'], 'code');
echo '<a href="' . $url . '">FB login</a>';

// If we got access code...
if (isset($_GET['code'])) { 

    // ...try to get access token with code grant type
    $data = $oauth->getAccessTokenByCode($_GET['code'], 'GET');
    print_r($data);
}

// If we got access token, validate it (optional)   
if (isset($data['access_token'])) {
    $info = $oauth->getTokenInfo($data['access_token'], $data['access_token'], 'GET');
    print_r($info);
}
    
// If token is valid, access protected resources
if (isset($info['is_valid']) && $info['is_valid']) {
    
    // Get protected resources...
}
```

#### Google login example
```php
// Instatiate required classes
$http = new Http();
$oauth = new OAuth2Client($http);

// Setup

// Your callback URL after authorization
$oauth->setRedirectUri('https://localhost/login/');

// API end points
$oauth->setAuthorizeUrl('https://accounts.google.com/o/oauth2/v2/auth');
$oauth->setAccessTokenUrl('https://www.googleapis.com/oauth2/v4/token');
$oauth->setValidateTokenUrl('https://www.googleapis.com/oauth2/v2/tokeninfo');

// API credentials (create yours at https://console.developers.google.com)
$oauth->setClientId('xxx');
$oauth->setClientSecret('xxx');

// Make API calls

// Define scope
$scope = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
];

// Create login URL with specified scope and grand type
$url = $oauth->getLoginUrl($scope, 'code');
echo '<a href="' . $url . '">Google login</a>';

// If we got access code...
if (isset($_GET['code'])) { 

    // ...try to get access token with code grant type
    $data = $oauth->getAccessTokenByCode($_GET['code']);
    print_r($data);
}

// If we got access token, validate it (optional)   
if (isset($data['access_token'])) {
    $info = $oauth->getTokenInfo($data['access_token'], $data['access_token']);
    print_r($info);
}
    
// If token is valid, access protected resources
if (isset($info['is_valid']) && $info['is_valid']) {
    
    // Get protected resources...
}
```
 
## Description of provided methods

#### setRedirectUri(string $url)
Sets callback URL. Callback URL should be URL at your web site. User will be redirected to this URL after authorization against OAuth provider.

#### setClientSecret(string $secret)
Sets API consumer secret.

#### setClientId(string $id)
Sets API consumer key.

#### setAuthorizeUrl(string $url)
Sets API endpoint for authorization.

#### setAccessTokenUrl(string $url)
Sets API endpoint for getting the access token.

#### setValidateTokenUrl(string $url)
Sets API endpoint for validating the access token.

#### getLoginUrl(bool $scope, string $responseType, bool $state):string
Returns authorization URL with all required parameters.

#### getAccessTokenByCode(string $code, string $method):array
On success returns array with 'access_token', 'token_type', 'expires_in' and other data if provided. On error returns array with response 'header', 'body', 'err' and 'info'.

#### getAccessTokenByPassword(string $username, string $password, string $method):array
#### getAccessTokenByCredentials(string $username, string $password, string $method):array
#### getAccessTokenByRefreshToken(string $refreshToken, string $method):array

#### getTokenInfo(string $inputToken, string $accessToken, string $method):array
On success returns array with various keys. On error returns array with response 'header', 'body', 'err' and 'info'.