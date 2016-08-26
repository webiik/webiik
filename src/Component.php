<?php
namespace Webiik;

class Component
{
    private $compDir;
    private $tmpDir;
    private $jsDir;
    private $cssDir;
    private $imgDir;

    public function __construct($compDir, $tmpDir, $jsDir, $cssDir, $imgDir)
    {
        $this->compDir = rtrim($compDir, '/');
        $this->tmpDir = rtrim($tmpDir, '/');
        $this->jsDir = rtrim($jsDir, '/');
        $this->cssDir = rtrim($cssDir, '/');
        $this->imgDir = rtrim($imgDir, '/');
    }

    public function install($url)
    {
        // Todo: Lock /tmp/

        // Delete everything in /tmp/
        $this->rrmdir($this->tmpDir);

        // Upload zip file to /tmp/tmpfile.zip
        file_put_contents($this->tmpDir . '/tmpfile.zip', fopen($url, 'r'));

        // Unpack zip file
        $zip = new \ZipArchive();
        $res = $zip->open($this->tmpDir . '/tmpfile.zip');
        if ($res === TRUE) {
            $zip->extractTo($this->tmpDir);
            $zip->close();
        }

        // Delete zip file
        unlink($this->tmpDir . '/tmpfile.zip');

        // Try to find desc.php file
        if (file_exists($this->tmpDir . '/desc.php')) {
            $tmpComponentDir = $this->tmpDir;
            $desc = include $this->tmpDir . '/desc.php';
        }

        // Open first dir and try to find desc.php file
        if (!isset($desc)) {

            $dirContent = glob($this->tmpDir . '/*');
            foreach ($dirContent as $entry) {
                if (is_dir($entry)) {
                    $tmpComponentDir = $entry;
                    break;
                }
            }

            if (isset($tmpComponentDir) && file_exists($tmpComponentDir . '/desc.php')) {
                $desc = include $tmpComponentDir . '/desc.php';
            }
        }

        if (isset($desc) && isset($tmpComponentDir)) {

            // Protect against vulnerable inputs like /../../app
            if (preg_match('/\w*/', $desc['namespace']) === true && preg_match('/\w*/', $desc['componentName']) === true) {

                $componentFolder = strtolower($desc['namespace'] . '/' . $desc['componentName']);

                // Delete old component files, if exists
                $dirsToDelete = [];
                $dirsToDelete[] = $this->compDir . '/' . $componentFolder;
                $dirsToDelete[] = $this->jsDir . '/' . $componentFolder;
                $dirsToDelete[] = $this->cssDir . '/' . $componentFolder;
                $dirsToDelete[] = $this->imgDir . '/' . $componentFolder;
                foreach ($dirsToDelete as $dir) {
                    if (file_exists($dir)) {
                        $this->rrmdir($dir, true);
                    }
                }

                // Prepare folders for installed component
                mkdir($this->compDir . '/' . $componentFolder, 0777, true);
                if (count(glob($tmpComponentDir . '/assets/*')) > 1) {

                }

                // Copy new component to component folder excl. assets folder


                $this->rcopy();

            }

            // Copy assets from asset folder to their locations

            // Delete all files in /tmp/

            // Add component to /components/components.php
        }

        // Todo: Unlock /tmp/
    }

    public function uninstall($namespace, $class)
    {
        // Delete component assets from assets dir

        // Delete component from component folder

        // Remove component from /components/components.php
    }

    public function activate($namespace, $class)
    {
        // Activate component in /components/components.php
    }

    public function deactive($namespace, $class)
    {
        // Deactivate component in /components/components.php
    }

    public function getTemplateComponents()
    {
    }

    public function runTemplateComponents()
    {
    }

    private function rrmdir($dir, $inclusive = false)
    {
        if ($inclusive === true) {
            $inclusive = $dir;
        }

        foreach (glob($dir . '/*') as $entry) {
            if (is_dir($entry)) {
                $this->rrmdir($entry, $inclusive);
            } else {
                unlink($entry);
            }
        }

        if ($dir != $inclusive) {
            rmdir($dir);
        }
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     * @return bool
     */
    function rmove($src, $dest)
    {

        // If source is not a directory stop processing
        if (!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), $dest . '/' . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                $this->rmove($f->getRealPath(), $dest . '/' . $f->getFilename());
                unlink($f->getRealPath());
            }
        }
        unlink($src);

        return true;
    }
}