<?php
/**
 * This CLI script provides single component installation
 */

// Read config file

// Ask for plug-in source dir or git hub repo

// Read desc.php

// Look inside installed.php and check if plug-in is already installed

// If is installed. Show version of installed plug-in and of new plug-in.
// Ask for continue installing new plug-in. Old plug-in will be deleted.

// Create TMP folder(uniqid().time()) in tmp folder

// Copy/download plug-in dir/repo to TMP folder

// If it's repo, unzip the repo inside TMP folder and delete zip

// Iterate components inside components folder and read they desc.php files

    // Look inside ./components/installed.php and notice user if there is already same component. Ask if continue.

    // Move component assets to ./assets/namespace/class/

    // Add record to installed.php

// Go inside first folder in TMP folder and find desc.php

// Move/rename TMP folder to plugins/plug-in-folder

// Add record to installed.php