# OAuth 1 client
Tiny OAuth 1 client for your web app. Only one class and 125 lines of code you will easily understand. Use it as it is or rewrite it for your needs.

## Supported providers
- every that uses OAuth 1 (Twitter, LinkedIn, Vimeo, Dropbox,...)  

## Installation
OAuth1 client is part of [Webiik platform](readme.md). Before using OAuth2 in your project, install it with the following command:
```bash
composer require mihi/webiik
```

## How to use it?

#### Twitter login example
```php
// Instatiate required classes
$http = new Http();
$token = new Token();
$oauth = new OAuth1Client($http, $token);

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
      
      // Get protected resources...
}

print_r($accessTokenData);
```

## OAuth 1 flow
And here is the visual explanation of code above. See how OAuth 1 works.
![Image of Yaktocat](https://oauth.net/core/diagram.png)
    
## Description of provided methods

#### setCallbackUrl(string $url)
Sets callback URL. Callback URL should be URL at your web site. User will be redirected to this URL after authorization against OAuth provider.

#### setConsumerSecret(string $secret)
Sets API consumer secret.

#### setConsumerKey(string $key)
Sets API consumer key.

#### setSignatureSecret(string $secret)
Sets API signature secret.

#### setReqestTokenUrl(string $url)
Sets API endpoint for getting the request token.

#### setAccessTokenUrl(string $url)
Sets API endpoint for getting the access token.

#### setAuthorizeUrl(string $url)
Sets API endpoint for authorization.

#### getRequestTokenData():array
On success returns array with 'oauth_token' and other data if provided. On error returns array with response 'header', 'body', 'err' and 'info'.

#### getLoginUrl(string $requestToken):string
Returns authorization URL with filled request token.

#### redirectToLoginUrl(string $requestToken)
Redirects to authorization URL with filled request token.

#### getAccessTokenData():array
On success returns array with 'oauth_token' and other data if provided. On error returns array with response 'header', 'body', 'err' and 'info'.
