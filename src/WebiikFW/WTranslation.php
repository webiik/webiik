<?php

namespace Webiik;

class WTranslation extends Translation
{
    /** @var array */
    private $WConfig;

    /** @var Request */
    private $request;

    /** @var Arr */
    private $arr;

    /**
     * WTranslation constructor.
     * @param array $WConfig
     */
    public function __construct(Request $request, Arr $arr, $WConfig)
    {
        $this->request = $request;
        $this->arr = $arr;
        $this->WConfig = $WConfig;

        // Set app main lang (can redirect)
        $this->initLangFromURI();

        // Set translation fallback languages
        $this->setFallbackLangs();
    }

    /**
     * Get array of fallback languages for given lang
     * @param string $lang
     * @return array
     */
    public function getFallbackLangs($lang)
    {
        $langs = $this->WConfig['Translation']['languages'];

        if (isset($langs[$lang][1]) && is_array($langs[$lang][1])) {
            $fallbackLangs = $langs[$lang][1];
        } else {
            $fallbackLangs = [];
        }

        return $fallbackLangs;
    }

    /**
     * Load translation from file into Translation service
     * @param string $fileName
     * @param string $dir - Dir inside /app/translations/
     * @param bool $addOnlyDiffTranslation - Add only missing translations in current lang
     * @param bool $key - When $addOnlyDiffTranslation is false, you can specify what part of translation will be added
     * @param null|string|array $lang
     */
    public function loadTranslations($fileName, $dir = '', $addOnlyDiffTranslation = true, $key = false, $lang = null)
    {
        if (is_array($lang)) {
            $fl = $lang;
            $lang = array_shift($fl);
        }
        $lang = $lang ? $lang : $this->lang;

        $dir = $dir == '' ? '' : trim($dir, '/') . '/';

        // Add translation for required lang
        $file = $this->WConfig['WebiikFW']['privateDir'] . '/app/translations/' . $dir . $fileName . '.' . $lang . '.php';
        if (file_exists($file)) {
            $translation = require $file;
            if ($key) {
                $this->addTrans($lang, $translation[$key], $key);
            } else {
                $this->addTrans($lang, $translation);
            }
        }

        // Get fallback languages and iterate them
        if (isset($fl)) {
            $fl = array_merge($this->getFallbackLangs($this->lang), $fl);
        } else {
            $fl = $this->getFallbackLangs($this->lang);
        }
        foreach ($fl as $flLang) {

            $file = $this->WConfig['WebiikFW']['privateDir'] . '/app/translations/' . $dir . $fileName . '.' . $flLang . '.php';

            // Load fallback translations
            if (file_exists($file)) {

                // Get translation for current lang
                $currentLangTranslation = $this->_tAll($this->lang);

                // Get translation for iterated fallback
                $translation = require $file;

                if (!$addOnlyDiffTranslation) {

                    // Add translation
                    if ($key) {
                        $this->addTrans($flLang, $translation[$key], $key);
                    } else {
                        $this->addTrans($flLang, $translation);
                    }
                }

                // Find keys that missing in current lang translation
                $missingTranslations = $this->arr->diffMultiABKeys($currentLangTranslation, $translation);

                // Add this missing translation
                foreach ($missingTranslations as $key => $val) {
                    $this->addTrans($flLang, $val, $key);
                }
            }
        }
    }

    /**
     * Try to find lang in URI and if there is no valid lang, use default lang.
     * Redirect access from '/' to '/dl/' when default lang has to be in URI.
     */
    private function initLangFromURI()
    {
        $lang = false;
        $langs = $this->WConfig['Translation']['languages'];

        // Get web root URI
        $uri = str_replace($this->request->getWebRootPath(), '', $_SERVER['REQUEST_URI']);

        // Did we find some language in web root URI?
        preg_match('/^\/([\w]{2})\/?$|^\/([\w]{2})\//', $uri, $matches);

        if (count($matches) > 0) {

            // Yes we do...
            foreach ($langs as $ilang => $prop) {

                // Check if the lang is valid lang...
                if ($ilang == $matches[1]) {
                    $lang = $matches[1];
                    break;
                }

                if (isset($matches[2]) && $ilang == $matches[2]) {
                    $lang = $matches[2];
                    break;
                }
            }
        }

        if ($uri == '/') {

            // It's root URI, so we always set the default lang as main lang
            $lang = key($langs);

            // If default lang has to be in URI, redirect to URL with default lang
            if ($this->WConfig['Router']['dlInUri']) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location:' . $this->request->getWebRootUrl() . '/' . $lang . '/');
                exit;
            }

        } else {

            // It's not root URI...

            if (!$lang) {
                $lang = key($langs);
            }
        }

        $this->lang = $lang;

    }

    /**
     * Set fallback language(s) for all available languages
     */
    private function setFallbackLangs()
    {
        $langs = $this->WConfig['Translation']['languages'];
        foreach ($langs as $lang => $p) {
            if (isset($p[1][0])) {
                $this->setFallback($lang, $p[1]);
            }
        }
    }
}