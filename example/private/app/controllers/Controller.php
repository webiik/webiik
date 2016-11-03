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
//        $trans = new Translation();
//        $trans->setLang('en');
//        $trans->setFallbacks('en', ['cs']);
//        $trans->setFallbacks('cs', ['sk']);

//        $trans->addTrans('en', 'Welcome', 'g1');
//        $trans->addTrans('cs', 'Vítejte', 'g1');
//        $trans->addTrans('sk', 'Vítajte', 'g1');

//        echo $trans->_t('g1');

//        print_r($this->translation->_t('t7'));

//        print_r($this->translation->_tAll());

        echo $this->router->getUriFor('account', 'cs');

        $data = [
            'seo' => [
                'title' => $this->translation->_t('seo.title'),
                'desc' => $this->translation->_t('seo.desc'),
            ],
            't2' => $this->translation->_p('t2', ['numCats' => 1]),
        ];

        echo $this->twig->render('home.twig', $data);

    }
}