<?php
namespace Webiik;


class Render
{
    private $renderHandler;

    public function addRenderHandler($handler)
    {
        $this->renderHandler = $handler;
    }

    public function render($options = [])
    {
        $rh = $this->renderHandler;
        return $rh(...$options);
    }
}