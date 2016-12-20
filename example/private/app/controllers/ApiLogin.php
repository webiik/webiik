<?php
namespace Webiik;

class ApiLogin
{
    private $router;
    private $auth;
    private $flash;
    private $csrf;
    private $config;

    public function __construct(Router $router, Auth $auth, Flash $flash, Csrf $csrf, $config)
    {
        $this->router = $router;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->config = $config['authAPI'];
    }

    public function run()
    {
        $res = [];

        // Set JSON header
        $response = new Response();
        $response->setContentType('json');

        // Validate secret
        $this->validateSecret();

        // Define awaited inputs
        $awaitedInputs = [
            'email',
            'pswd',
        ];

        // Check if we have all awaited inputs
        $this->validateInputs($awaitedInputs, 'POST');

        // Try to get user from DB
        $user = $this->auth->userGet($_POST['email'], $_POST['pswd']);


        if (is_array($user)) {

            // Do we get array? It means that user exists

            $res['status'] = 'ok';

            $res['messages'][] = 'User was successfully logged in.';
            $res['user']['id'] = $user['uid'];

            if (isset($user['status'])) {
                $res['user']['status'] = $user['status'];
            }

        } else {

            // User does not exits

            $res['status'] = 'err';

            if ($user == -3) {
                $res['messages'][] = 'Too many login attempts.';
                $res['err_code'] = -3;
            } else if ($user == -2) {
                $res['messages'][] = 'User does not exist.';
                $res['err_code'] = -2;
            } else if ($user == -1) {
                $res['messages'][] = 'User account expired.';
                $res['err_code'] = -1;
            } else if ($user == 0) {
                $res['messages'][] = 'Invalid password.';
                $res['err_code'] = 0;
            } else {
                $res['messages'][] = 'Unknown error.';
                $res['err_code'] = -4;
            }

        }

        echo json_encode($res);
        exit;
    }

    private function validateSecret()
    {
        $res = [];
        $req = new Request();
        $secret = $req->getHeader('X-WEBIIK-SECRET');

        if($secret != $this->config['secret']){
            $res['status'] = 'err';
            $res['err_code'] = -5;
            $res['messages'][] = 'Unauthorized access.';
        }

        // If res is not empty we have got some error
        if (!empty($res)) {
            echo json_encode($res);
            exit;
        }
    }

    /**
     * @param $inputs
     * @param string $method
     */
    private function validateInputs($inputs, $method = 'GET')
    {
        $res = [];

        foreach ($inputs as $input) {

            if ($method == 'POST' && !isset($_POST[$input])) {
                $res['status'] = 'err';
                $res['err_code'] = -6;
                $res['messages'][] = 'Missing POST input: \'' . $input . '\'';
                $res['missing'][] = $input;
            }

            if ($method == 'GET' && !isset($_GET[$input])) {
                $res['status'] = 'err';
                $res['err_code'] = -6;
                $res['messages'][] = 'Missing GET input: \'' . $input . '\'';
                $res['missing'][] = $input;
            }
        }

        // If res is not empty we have got some error
        if (!empty($res)) {
            echo json_encode($res);
            exit;
        }
    }
}