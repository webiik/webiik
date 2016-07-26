<?php
namespace Webiik;
use Pimple\Container;

/**
 * Class Route
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 *
 * Route is just helper class for the adding route middlewares.
 * Method 'map' in Webbik's class Core returns this Route object.
 */
class Route
{
    /**
     * @var int
     */
    private $routeId;

    /**
     * @var Core
     */
    private $core;

    /**
     * @param int $routeId
     */
    public function __construct($routeId, Core $core)
    {
        $this->routeId = $routeId;
        $this->core = $core;
    }

    /**
     * @param $mw
     * @return $this
     */
    public function add($mw, $args = null)
    {
        $this->core->add($mw, $args, $this->routeId);
        return $this;
    }
}