<?php
require 'Filesystem.php';

$fs = new \Webiik\Filesystem();

if (!isset($argv[1])) {
    echo 'Missing argument!' . "\n";
    echo 'Choose installation type:' . "\n";
    echo 'install.php core' . "\n";
    echo 'install.php skeleton' . "\n";
    exit;
}

$currentFolder = getcwd();

function getDirPath($privateFolder, $publicFolder){

    $privateFolderArr = explode('/', trim($privateFolder, '/'));
    $publicFolderArr = explode('/', trim($publicFolder, '/'));

    $i = 0;
    $downDirs = '';
    foreach ($privateFolderArr as $dir) {

        if(!isset($publicFolderArr[$i]) || $dir != $publicFolderArr[$i]){
            $downDirs = $downDirs . '/' . $dir;
        }
        $i++;
    }

    $i = 0;
    $upDirs = '';
    foreach ($publicFolderArr as $dir) {

        if(!isset($privateFolderArr[$i]) || $dir != $privateFolderArr[$i]){
            $upDirs = $upDirs . '/..';
        }
        $i++;
    }

    return $upDirs . $downDirs;
}

if ($argv[1] == 'skeleton') {

    // If user uses Apache, we will create .htaccess files
    echo 'Do you use Apache web server (y)/n?: ';
    $handle = fopen("php://stdin", "r");
    $isApache = trim(fgets($handle)) == 'n' ? false : true;

    echo "\n";

    echo 'Current folder is:' . $currentFolder . "\n";
    echo "\n";

    // If current folder is web root folder, we will create private folder inside this folder and
    // if user uses Apache we will create also .htaccess that denies access to private folder.
    // If current folder isn't web root folder, we will use this folder as private folder and
    // we will ask user for public folder location.
    echo 'Is current folder web root folder (y)/n?: ';
    $handle = fopen("php://stdin", "r");
    $isWebRoot = trim(fgets($handle)) == 'n' ? false : true;

    echo "\n";

    if ($isWebRoot) {

        $privateFolder = $currentFolder . '/private';
        $publicFolder = $currentFolder;

    } else {

        echo 'Current folder is used as private folder.' . "\n";
        $privateFolder = $currentFolder;

        echo "\n";

        echo 'Enter path to public folder: ';
        $handle = fopen("php://stdin", "r");
        $publicFolder = trim(fgets($handle));

        // If user set same folders for both, that means that private folder is public folder
        if ($privateFolder == $publicFolder) {
            $privateFolder = $publicFolder . '/private';
            $isWebRoot = true;
        }

        echo "\n";
    }

    // Show warning when private folder is inside public folder
    if (substr($privateFolder, 0, strlen($publicFolder)) == $publicFolder) {

        echo 'SECURITY WARNING! Private folder is inside web root folder.' . "\n";
        echo 'Make you sure that private folder is not accessible from web browser.' . "\n";
        echo "\n";

    }

    // Try to create folders
    if ($fs->mkdir($publicFolder) === false) {
        echo 'Can\'t create public folder. Probably due to missing permissions.';
        echo "\n";
        exit;
    };

    if ($fs->mkdir($privateFolder) === false) {
        echo 'Can\'t create private folder. Probably due to missing permissions.';
        echo "\n";
        exit;
    };

    // Create .htaccess files
    if ($isApache) {

        // Create root .htaccess
        $file = $publicFolder . '/.htaccess';
        $data = "RewriteEngine On
#RewriteBase /
RewriteRule /\.|^\. - [F]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule !\.(pdf|js|ico|gif|jpg|png|css|rar|zip|tar\.gz) index.php [QSA,L]";
        file_put_contents($file, $data);

        // Secure private web folder if is in web root folder
        if ($isWebRoot) {
            $file = $publicFolder . '/private/.htaccess';
            $data = "deny from all";
            file_put_contents($file, $data);
        }
    }

    // Create index.php
    $file = $publicFolder . '/index.php';
    $data = "<?php
// Autoload
require __DIR__ . '".getDirPath($privateFolder, $publicFolder)."/vendor/autoload.php';

// Bootstrap app
require __DIR__ . '".getDirPath($privateFolder, $publicFolder)."/app/bootstrap.php';";
    file_put_contents($file, $data);

    // Create folders
    $fs->mkdir($privateFolder . '/app');
    $fs->mkdir($publicFolder . '/assets');
    $fs->mkdir($publicFolder . '/assets/css');
    $fs->mkdir($publicFolder . '/assets/js');
    $fs->mkdir($publicFolder . '/assets/img');

    // Todo: Download app folder from github to private folder / app

    // via api
    // 'https://github.com/Jiri-Mihal/RocketRouter' -> user: Jiri-Mihal repo: RocketRouter
    // https://api.github.com/repos/User/repo/zipball/master
    // https://api.github.com/repos/Jiri-Mihal/RocketRouter/releases
    // curl -L https://api.github.com/repos/Jiri-Mihal/RocketRouter/zipball/master > RocketRouter.zip
    // /repos/:owner/:repo/contents/:path
    // https://api.github.com/repos/Jiri-Mihal/RocketRouter/contents/README.md

}