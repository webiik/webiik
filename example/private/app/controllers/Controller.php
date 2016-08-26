<?php
namespace Webiik;

class Controller
{
    private $translation;
    private $connection;
    private $routeInfo;
    private $router;

    /**
     * Controller constructor.
     */
    public function __construct(
        $response,
        $routeInfo,
        Connection $connection,
        Translation $translation,
        Router $router,
        AuthMiddleware $auth
    )
    {
        $this->routeInfo = $routeInfo;
        $this->translation = $translation;
        $this->connection = $connection;
        $this->router = $router;
        $this->auth = $auth;

        print_r($routeInfo);
        print_r($response);
    }

    public function run()
    {
        // Connect to DB
        $pdo = $this->connection->connect('db1');

        // Get page translation
        echo $this->translation->_p('t10', ['speed' => '100']);

        // Get route URI in some lang
        // Todo: Authenticate user (Think about how authentication will work - actions, MW)
        //if ($this->auth->can('use-uri-for')) {
        echo '<br/>CS URI: ' . $this->router->getUriFor('account', 'cs') . '<br/>';
        echo 'SK URI: ' . $this->router->getUriFor('account', 'sk') . '<br/>';
        echo 'EN URI: ' . $this->router->getUriFor('account', 'en') . '<br/>';
//        }

        // This should be inside controller
        $this->view->addComponentData($paramName, $paramVal, $namespace, $componentClassName);

        // This should be inside render method
        $this->components->incorporate();

        // This should be inside Components class
        $components = $this->getTemplateComponents($template);

        foreach ($components as $component) {

            // Check if component exists

            $namespace = $component['namespace'];
            $className = $component['className'];
            $methodName = $component['methodName'];
            $params = $component['params']; // jsFolder, imgFolder, cssFolder

            // If parameter has no data, try to add data from external source
            foreach($params as $paramName => $paramVal) {
                if(!$paramVal && isset($compData[$namespace][$className][$paramName])){
                    $params[$paramName] = $compData[$namespace][$className][$paramName];
                }
            }

            // Prepare Translation

            // Instantiate model

            // Instantiate component and inject dependencies and get final data
            // $params = $className->$methodName(...$params);

            // Render component with data from component instance
            // $componentHtml = $this->view->parse($componentTemplate, $params);

            // Replace component tags in template with component view
            // $template = $this->view->component->draw($template, $componentHtml, $matchingRegex);
        }

        // Todo: Render page using some template engine (Twig)
//        $this->view->render($template, $data);
        echo '<br/>';
        echo 'Controller';

        // Todo: PLUG-INS, COMPONENTS design
    }
}