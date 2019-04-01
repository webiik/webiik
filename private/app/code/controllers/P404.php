<?php
declare(strict_types=1);

namespace Webiik\Controller;

class P404
{
    public function run(\Webiik\Data\Data $data): void
    {
        echo '404';
    }
}