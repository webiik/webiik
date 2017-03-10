<?php
namespace Webiik;


/**
 * Class Render
 * This class provides abstraction above rendering the templates. Thanks to it
 * you will use same render->render() method with different template engines.
 * and you don't need to rewrite your controllers when you change the template engine.
 * @package Webiik
 */
class Render
{
    private $renderHandler;

    /**
     * Add simple file include as renderer.
     * For everyone who doesn't need to use template engine.
     * @param string $templateDir
     */
    public function addFileRenderHandler($templateDir = '')
    {
        $renderHandler = function ($template) use ($templateDir) {
            require $templateDir . $template;
        };
        $this->addRenderHandler($renderHandler);
    }

    /**
     * Use this method to add render handler of your desired template engine
     * @param $handler
     */
    public function addRenderHandler(\Closure $handler)
    {
        $this->renderHandler = $handler;
    }

    /**
     * Use this method to render templates
     * @param array $options
     * @return mixed
     */
    public function render($options = [])
    {
        $rh = $this->renderHandler;
        return $rh(...$options);
    }
}