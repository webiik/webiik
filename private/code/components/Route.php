<?php
declare(strict_types=1);

namespace Webiik\Translation\Parser;

use Webiik\Router\Router;
use Webiik\Translation\TranslationTrait;

class Route implements ParserInterface
{
    use TranslationTrait;

    /**
     * @var Router
     */
    private $router;

    /**
     * Route constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Syntax:
     * {Route, {link text} {route name} {target}}
     *
     * Example:
     * Go back to {Route, {Home Page} {home} {_blank}}.
     *
     * @param string|int $varValue
     * @param string $parserString
     * @return string
     */
    public function parse($varValue, string $parserString): string
    {
        $brackets = $this->extractBrackets($parserString);

        // {Home Page} {home} {_blank}
        $text = isset($brackets[0]) ? $brackets[0] : '';
        $url = isset($brackets[1]) ? $this->router->getURL($brackets[1]) : '';
        $target = isset($brackets[2]) ? ' target="' . $brackets[2] . '"' : '';

        if ($url && $text) {
            $parserString = '<a href="' . $url . '"' . $target . '>' . $text . '</a>';
        }

        return $parserString;
    }
}
