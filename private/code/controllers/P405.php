<?php
declare(strict_types=1);

namespace Webiik\Controller;

class P405
{
    public function run(\Webiik\Data\Data $data): void
    {
        header('HTTP/1.1 405 Method Not Allowed');
        echo '405';
    }
}