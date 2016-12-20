<?php
namespace Webiik;

class Page
{
    private $translation;
    private $twig;

    /**
     * Controller constructor.
     */
    public function __construct(Translation $translation, \Twig_Environment $twig)
    {
        $this->translation = $translation;
        $this->twig = $twig;
    }

    public function run()
    {
        // Get merged translations
        // We always get all shared translations and translations only for current page,
        // because Skeleton save resources and adds only these data to Translation class
        $translations = $this->translation->_tAll(false);

        // Parse some values
        $translations['t1'] = $this->translation->_p('t1', ['timeStamp' => time()]);
        $translations['t2'] = $this->translation->_p('t2', ['numCats' => 1]);

        // Render page
        echo $this->twig->render('home.twig', $translations);
    }
}