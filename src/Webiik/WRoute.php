<?php
namespace Webiik;

/**
 * Class WebiikRoute
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 *
 * Route is just helper class for the adding route middlewares.
 * Method 'map' in Webbik's class Core returns this Route object.
 */
class WRoute
{
    /**
     * @var int
     */
    private $routeId;

    /**
     * @var WMiddleware
     */
    private $mw;

    /**
     * @param int $routeId
     */
    public function __construct($routeId, WMiddleware $mw)
    {
        $this->routeId = $routeId;
        $this->mw = $mw;
    }

    /**
     * Add route middleware
     * @param $mw
     * @param null $args
     * @return $this
     */
    public function add($mw, $args = null)
    {
        $this->mw->add($mw, $args, $this->routeId);
        return $this;
    }

    /**
     * Return current route id
     * @return int
     */
    public function getId()
    {
        return $this->routeId;
    }
}