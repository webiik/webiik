<?php
/**
 * This CLI script provides single component installation
 */

// Read config file

// Ask for component source dir or git hub repo

// Read desc.php

// Look inside installed.php and check if component is already installed

// If is installed. Show version of installed component and of new component.
// Ask for continue installing new component. Old component will be deleted.

// Create TMP folder(uniqid().time()) in tmp folder

// Copy/download component dir/repo to TMP folder

// If it's repo, unzip the repo inside TMP folder and delete zip

// Go inside first folder in TMP folder and find desc.php

// Create folder structure inside components folder according to desc.php > components/namespace/class

// Move assets from TMP/first-folder to ./assets/namespace/class/

// Iterate content of TMP/first-folder and move it inside components/namespace/class

// Add record to installed.php









// via api
        // 'https://github.com/Jiri-Mihal/RocketRouter' -> user: Jiri-Mihal repo: RocketRouter
        // https://api.github.com/repos/User/repo/zipball/master
        // https://api.github.com/repos/Jiri-Mihal/RocketRouter/releases
        // curl -L https://api.github.com/repos/Jiri-Mihal/RocketRouter/zipball/master > RocketRouter.zip
        // /repos/:owner/:repo/contents/:path
        // https://api.github.com/repos/Jiri-Mihal/RocketRouter/contents/README.md