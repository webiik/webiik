<?php
namespace Webiik;

/**
 * Class WebiikRender
 * This class provides abstraction above rendering the templates.
 * @package Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class WRender
{
    private $renderHandler;

    private $translation;

    /**
     * WRender constructor.
     * @param $translation
     */
    public function __construct(WTranslation $translation)
    {
        $this->translation = $translation;
    }

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
     * @param \Closure $handler
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

    /**
     * Use this method to render any additional template eg. like email.
     * This method loads also the translation file for the template.
     * Don't use this method to render common templates.
     * @param $template
     * @param array $data
     * @param string $dir
     * @return mixed
     */
    public function renderWithTranslation($template, $data = [], $dir = '')
    {
        $dir = $dir == '' ? '' : '/' . trim($dir, '/') . '/';

        // Load email translations
        $this->translation->loadTranslations($template, $dir);
        $translations = $this->translation->_tAll(false);

        // Merge translations with additional data
        $data = array_merge($translations, $data);

        // Return rendered email template
        return $this->render([$dir . $template, $data]);
    }
}