# Auth
Safe user authentication and authorisation for your website:

- penetration monitoring
- smart optional account activation  
- permanent login option
- password renewal
- user roles and actions

## Installation
1. Auth is part of [Webiik platform](readme.md). Before using Auth in your project, install it with the following command:
    ```bash
    composer require mihi/webiik
    ```
2. Prepare [auth]() and [attempts]() database tables
3. Setup connection to your database
    ```php
    $connection = new \Webiik\Connection();
    $connection->add('app', 'mysql', 'localhost', 'webiik', 'root', 'root'); // Change it to your values
    ```
4. Instantiate Auth class
    ```php
    $auth = new \Webiik\Auth(new \Webiik\Sessions(), $connection, new \Webiik\Token(), new \Webiik\Attempts());
    ```  
5. Configure Auth class
    ```php
    $auth->setSalt('YOUR-PASSWORD-SALT');
    ```
    
## Cookbook
In the cookbook you can find some useful recipes how to work with Auth. Please note that code of all examples is simplified for better understanding the problematics. Cookbook is written for inspiration not for copy/paste. Cookbook expects that you already did [Auth setup](#quick-setup-in-4-easy-steps).

#### Roles and privileges
Before you sign up you first user, you need to create roles and actions for your application.

1. At first add some roles
    ```mysql
    INSERT INTO `auth_roles` (role) VALUES ('user'); # id 1
    INSERT INTO `auth_roles` (role) VALUES ('admin'); # id 2
    ```
2. Then add some actions
    ```mysql   
    INSERT INTO `auth_actions` (action) VALUES ('manage-own-comments'); # id 1
    INSERT INTO `auth_actions` (action) VALUES ('manage-all-comments'); # id 2
    INSERT INTO `auth_actions` (action) VALUES ('access-admin'); # id 3
    ```
3. At last combine roles and actions
    ```mysql   
    # User can manage own comments
    INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (1, 1);
    
    # Admin can access admin and manage all comments
    INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (2, 2);
    INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (2, 3);
    ```

#### Sign-up the user into database
```php
// SIGN-UP PAGE

// Try to store user into database
$data = $auth->userSet($email, $pswd, $roleId);

if (is_array($data)) { // User was successfully signed up  
    
    // Get user id
    $uid = $data['uid'];
    
    // Log in the user
}

if ($data == -2) { // User with same email already exists   
}

if ($data == -3) { // Too many sign-up attempts from current IP  
}
```

#### Sign-up the user into database with required account activation
By default user has 24 hours to confirm activation. During this period he/she can log in to his/her account. If time limit expires and user does not activate the account, then user will not be able to log in to his/her account. Expired accounts are deleted during every call of `userSet` method.
```php
// ADD THIS TO YOUR AUTH SETUP 
// Set the account activation
$auth->setWithActivation(true);
```  
```php
// SIGN-UP PAGE

// Try to store user into database
$data = $auth->userSet($email, $pswd, $roleId);

if (is_array($data)) { // User was successfully signed up
    
    // Get user id
    $uid = $data['uid'];
    
    // Prepare activation link
    $link = 'https://YOUR-ACTIVATION-PAGE?data=' . $data['selector'] . ':' . $data['token'];
     
    // Send activation link to user by email
    
    // Log in the user
}

if ($data == -2) { // User with same email already exists   
}

if ($data == -3) { // Too many sign-up attempts from current IP  
}
```
```php 
// ACTIVATION PAGE

// Get selector and token
$data = explode(':', $_GET['data']);

if ($auth->userActivate($data[0], $data[1])) { // User was successfully activated      
    // Redirect user to login page
    $auth->redirect($loginPageUrl);
}   
```
```php
// RESEND ACTIVATION

if ($uid = $auth->isUserLogged()) { // user is logged in
    
    // Generate new activation token
    $data = $auth->userGenerateActivationToken($uid);
    
    if (is_array($data)) { // Token was successfully generated  
        
        // Prepare activation link
        $link = 'https://YOUR-ACTIVATION-PAGE?data=' . $data['selector'] . ':' . $data['token'];
        
        // Send activation link to user by email
    }
}
```

#### Forgotten password / password renewal
By default user has 24 hours to set new password after he/she requested password renewal. After successful password renewal, all his/her previous password renewal requests will be deleted. Expired password renewal requests are deleted, with 5% chance, during every call of `userGeneratePasswordToken` method.  
```php
// PASSWORD RENEWAL REQUEST PAGE

// Get email from web form. Note that email is also the user name
$email = $_POST['email'];

// Generate password renewal request
$data = $auth->userGeneratePasswordToken($email);

if (is_array($data)) { // Request was successfully generated
      
    // Prepare password renewal link
    $link = 'https://YOUR-PASSWORD-RENEWAL-CONFIRMATION-PAGE?data=' . $data['selector'] . ':' . $data['token'];
     
    // Send activation link to user by email
}

if ($data == -3) { // Too many renewal attempts from current IP  
}
```
```php 
// PASSWORD RENEWAL CONFIRMATION PAGE

// Get selector and token from URL
$data = explode(':', $_GET['data']);

// Get new password from web form
$newPassword = $_GET['pswd'];

if ($auth->userChangePassword(($data[0], $data[1], $newPassword)) { // Password successfully changed
    // Redirect user to login page
    $auth->redirect($loginPageUrl);
}   
```

#### Check if user is in database
```php    
// Get user from database by email and password
$data = $auth->userGet($email, $pswd);

if (is_array($data)) { // User is in database
    
    // Get user id
    $uid = $data['uid'];
       
    if (isset($data['status']) && $data['status'] == 0) {
        // User did not activate the account yet
    }
    
    if (isset($data['status']) && $data['status'] == 1) {
        // User already activated the account
    }
}

if ($data == -3) { // Too many attempts   
}

if ($data == -2) { // User is not in database    
}

if ($data == -1) { // User account expired
}

if ($data == 0) { // User exists but password does not match    
}

```

#### Temporary login the user into app
Temporary login is session based so it is valid till session is valid or web browser is closed.
```php    
$auth->login($uid);
```

#### Permanent login the user into app
Permanent login is valid till permanent login cookie exists and is valid.
```php
// Set name of permanent login cookie
$auth->setCookieName('MyPermanentCookie');

// Permanent login for 14 days
$auth->login($uid, 14);  
```

#### Logout the user from app
If user is logged in at more devices. Logout at one device logs out the user on all devices.
```php    
$auth->logout();  
```        

#### Check if user is logged in and has privileges to perform some action
```php    
if ($auth->can('edit-post')) {
    // ...some code here
}  
```

## Description of provided methods

#### setSalt(string $string)
Sets password salt. No value is returned.
#### setCookieName(string $string)
Sets name of the permanent login cookie. No value is returned.
#### setPermanent(int $days)
Sets how many days is permanent login cookie valid. No value is returned.
#### setWithActivation(bool $bool)
Sets sign-up with required account activation. No value is returned.
#### setUserGetAttempts(int $count, int $sec)
Sets maximal count of attempts of getting the user from database for one IP address. No value is returned.
#### setUserSetAttempts(int $count, int $sec)
Sets maximal count of attempts of storing the user into database for one IP address. No value is returned.
#### setUserPswdAttempts(int $count, int $sec)
Sets maximal count of attempts of changing the user password for one IP address. No value is returned.
#### setConfirmationTime(int $sec)
Sets time that user has to confirm some auth action. No value is returned.
#### userLogin(int $uid)
Logs in the user. Creates session 'logged' with value of $uid. No value is returned.
If `setWithActivation` is true, it also creates permanent login cookie.
#### userLogout()
Logs out the user. Deletes and destroys all sessions. No value is returned.
If `setWithActivation` is true, it also deletes permanent login cookie.
#### isUserLogged()
Looks for session 'logged' and return user id if that session exists, otherwise false.
If `setWithActivation` is true, it also looks for permanent login cookie. It always validates cookie against database. It creates session 'logged' when session 'logged' does not exist and permanent login cookie is valid.
#### redirect(string $uri)
Redirects user only to URL on the same server.
#### userCan(string $action)
Calls `isUserLogged` and if gets user id then it searches in database if user can perform given action. Return user id on success, otherwise false. 
#### userSet(string $email, string $pswd, int $roleId)
Stores user in database. It also stores user's 'signup' attempts and with 10% probability deletes all expired 'signup' attempts. On success returns array with the following keys: 'uid', ('selector', 'token' - if `setWithActivation` is true). On error return -3 (too many attempts) or -2 (user already exists).
#### userGet(string $email, string $pswd)
Gets user from database. It also stores user's 'login' attempts and with 5% probability deletes all expired 'login' attempts. On success returns array with the following keys: 'uid', ('status' - if `setWithActivation` is true). On error return -3 (too many attempts), -2 (user does not exist), -1 (user exists but is expired), 0 (user exists but password does not match).
#### userActivate(string $selector, string $token)
Activates the user. It also stores user's 'signup' attempts and with 5% probability deletes all expired 'signup' attempts. Returns true on success otherwise false.
#### userGenerateActivationToken(int $uid)
Generates user activation token. It also stores user's 'signup' attempts and with 5% probability deletes all expired 'signup' attempts. On success returns array with the following keys: 'selector', 'token'. On error return -3 (too many attempts) or -2 (db error).
#### userChangePassword(string $selector, string $token, string $pswd)
Changes the user password. It also stores user's 'renewal' attempts and with 5% probability deletes all expired 'renewal' attempts. Returns true on success otherwise false.
#### userGeneratePasswordToken(string $email)
Generates user password renewal token. It also stores user's 'renewal' attempts and with 5% probability deletes all expired 'renewal' attempts. On success returns array with the following keys: 'selector', 'token'. On error return -3 (too many attempts) or -2 (db error).