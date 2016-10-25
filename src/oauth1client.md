# OAuth1 client
Tiny OAuth1 client for your web app. Only one class and 125 lines of code which you will easily understand. Use it as it is or rewrite it for your needs.

## Supported providers
- every that uses OAuth1 (Twitter, LinkedIn, Vimeo, Dropbox,...)  

## Installation
Before using OAuth1 in your project, add it to your `composer.json` file:
```bash
composer require webiik/oauth1
```
It will also install dependencies [webiik/http](), [webiik/token]().

## How to use it? (Twitter login example)
```php
// Instatiate required classes
$http = new Http();
$token = new Token();
$oauth = new OAuthOneClient($http, $token);

// Setup

// Your callback URL after authorization
$oauth->setCallbackUrl('https://localhost/login/');

// API end points
$oauth->setReqestTokenUrl('https://api.twitter.com/oauth/request_token');
$oauth->setAuthorizeUrl('https://api.twitter.com/oauth/authenticate');
$oauth->setAccessTokenUrl('https://api.twitter.com/oauth/access_token');

// API credentials (create yours at https://apps.twitter.com)
$oauth->setConsumerSecret('xxx');
$oauth->setConsumerKey('xxx');

// Make API calls

// Log in a user
if (!isset($_GET['oauth_verifier'])) { // cheap trick just for simplicity, you should do this better;)
    
    // Try to get request oauth_token (A)
    $requestTokenData = $oauth->getRequestTokenData();
    
    // If we have request oauth_token (B), redirect user to authorization page (C)
    if (isset($requestTokenData['oauth_token'])) {
        $oauth->redirectToLoginUrl($requestTokenData['oauth_token']);
    }
    
    print_r($requestTokenData);
}

// Try to get access oauth_token (D) (E)
$accessTokenData = $oauth->getAccessTokenData();

// If we have access oauth_token (F), access protected resources (G)
if (isset($accessTokenData['oauth_token'])) {
      
      // Get user info...
}

print_r($accessTokenData);
```
![Image of Yaktocat](https://oauth.net/core/diagram.png)
    
## Description of provided methods

__setCallbackUrl(string $url)__
Sets callback URL. Callback URL should be URL at your web site. User will be redirected to this URL after authorization against OAuth provider.

__setConsumerSecret(string $secret)__
Sets API consumer secret.

__setConsumerKey(string $key)__
Sets API consumer key.

__setSignatureSecret(string $secret)__
Sets API signature secret.

__setReqestTokenUrl(string $url)__
Sets API endpoint for getting the request token.

__setAccessTokenUrl(string $url)__
Sets API endpoint for getting the access token.

__setAuthorizeUrl(string $url)__
Sets API endpoint for authorization.

__getRequestTokenData():array__
On success returns array with 'oauth_token' and other data if provided. On error returns array with response 'header', 'body', 'err' and 'info'.

__getLoginUrl(string $requestToken):string__
Returns authorization URL with filled request token.

__redirectToLoginUrl(string $requestToken)__
Redirects to authorization URL with filled request token.

__getAccessTokenData():array__
On success returns array with 'oauth_token' and other data if provided. On error returns array with response 'header', 'body', 'err' and 'info'.
