<?php
namespace Webiik;

class Controller
{
    private $routeInfo;
    private $translation;
    private $connection;
    private $router;
    private $auth;
    private $sessions;
    private $twig;

    /**
     * Controller constructor.
     */
    public function __construct(
        $routeInfo,
        Connection $connection,
        Translation $translation,
        Router $router,
        Auth $auth,
        Sessions $sessions,
        \Twig_Environment $twig
    )
    {
        $this->routeInfo = $routeInfo;
        $this->translation = $translation;
        $this->connection = $connection;
        $this->router = $router;
        $this->auth = $auth;
        $this->sessions = $sessions;
        $this->twig = $twig;

//        print_r($routeInfo);
    }

    public function run()
    {
        // Connect to DB
//        $pdo = $this->connection->connect('user');

//        $this->sessions->setSessionDir(__DIR__ . '/../tmp');
//        $this->sessions->sessionStart();
//        $this->auth->setCookieName('myPC');
//        $this->auth->setWithActivation(true);
        //$this->auth->userGenerateActivationToken($uid);

//        print_r($this->auth->userSet('vlasta@mihal.me', 'test', 1));
//        $user = $this->auth->userGet('jiri@mihal.me', 'test');
//        $this->auth->userLogin($user['uid']);
        if ($this->auth->isUserLogged()) {
            echo 'AHOJ!';
        }

        if ($this->auth->userCan('edit-post')) {
            echo 'POST';
        }

        // Todo: Get translations for current page

        print_r($this->translation->_t('t7'));
//        print_r($this->translation->_tAll());

        $data = [
            'seo' => [
                'title' => $this->translation->_t('seo.title'),
                'desc' => $this->translation->_t('seo.desc'),
            ],
            't2' => $this->translation->_p('t2', ['numCats' => 1]),
        ];

        echo $this->twig->render('home.twig', $data);

        //print_r($http->get($url));
//        print_r($http->get($url));

        //$handle=curl_init($url);
//        curl_setopt($handle, CURLOPT_VERBOSE, true);
//        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
//        $content = curl_exec($handle);
//
//        if(!curl_errno($handle))
//        {
//            $info = curl_getinfo($handle);
//
//            echo '<br/> Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
//        }
//        else
//        {
//            echo 'Curl error: ' . curl_error($handle);
//        }
//
//        echo '<img src ="'.$content.'"/>';

//        print_r($this->auth->generateActivation(1));
//        echo $this->auth->userActivate('e5ad2d59d1fa', '2c319e4d888234eb89db621560dd2cfa');
//        echo $this->auth->userGet('jiri@mihal.me', 'test');

//        $this->auth->login(1, true);
//        $this->auth->userLogout();
//
//        if ($this->auth->userLogged()) {
//            echo 'Is logged in.';
//        }
//
//        if($this->auth->userCan('access-account')){
//            echo 'User can access account.';
//        }

//        if ($this->auth->isActivated(1)) {
//            echo 'Activated.';
//        } else {
//            echo 'Not activated.';
//        }


        // Get page translation
//        echo $this->translation->_p('t10', ['speed' => '100']);

        // Get route URI in some lang
//        echo '<br/>CS URI: ' . $this->router->getUriFor('account', 'cs') . '<br/>';
//        echo 'SK URI: ' . $this->router->getUriFor('account', 'sk') . '<br/>';
//        echo 'EN URI: ' . $this->router->getUriFor('account', 'en') . '<br/>';

        // Todo: Render page using some template engine (Twig)
//        $this->view->render($template, $data);
//        echo '<br/>';
//        echo 'Controller';

    }
}