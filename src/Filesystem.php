<?php
namespace Webiik;

/**
 * Class Filesystem
 * Handle basic file operations
 *
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Filesystem
{
    /**
     * Make given dir structure
     * On success return array of created dirs
     * On error return error message
     * @param $dir
     * @param int $privileges
     * @param bool $recursive
     * @param null $context
     * @return array|string
     */
    public function mkdir($dir, $privileges = 0777, $recursive = true, $context = null)
    {
        $newDirs = [];

        if (file_exists($dir)) {
            return $newDirs;
        }

        $dirs = explode('/', trim($dir, '/'));
        $dirPath = '';

        foreach ($dirs as $dir) {

            $dirPath .= '/' . $dir;

            if (file_exists($dirPath)) {
                continue;
            }

            if($context){
                $res = @mkdir($dirPath, $privileges, $recursive, $context);
            } else {
                $res = @mkdir($dirPath, $privileges, $recursive);
            }

            if (!$res) {
                // Todo: Delete all newly created dirs
                $err = error_get_last();
                return $err['message'] . ' in <b>' . $err['file'] . '</b> on line <b>' . $err['line'] . '</b>';
            }

            $newDirs[] = $dirPath;
        }

        if(count($newDirs) > 0){
            return $newDirs;
        }

        return 'Got no dir structure.';
    }

    public function delete($path)
    {
        return false;
    }

    /**
     * Check if file, dir or url exist
     * @param $path
     * @return bool
     */
    public function exists($path)
    {
        if (file_exists($path)) {
            return true;
        }

        if ($this->isUrl($path) && $this->urlExists($path)) {
            return true;
        }

        return false;
    }

    public function copy($src, $dest, $context = null)
    {
        $src = $this->stripTrailingSlashes($src);
        $dest = $this->stripTrailingSlashes($dest);

        // Destination must be local
        if ($this->isUrl($dest)) {
            return false;
        }

        // Copy local file
        if (is_file($src)) {

            if (!$newDirs = $this->mkdir($dest)) {
                return false;
            }

            if (is_dir($dest)) {
                $dest = $dest . '/' . $this->stripPathFromFile($src);
            }

            if (!copy($src, $dest, $context)) {
                foreach ($newDirs as $newDir) {
                    $this->delete($newDir);
                }
                return false;
            }

            return true;
        }

        // Copy local dir
        if (is_dir($src) && !$this->stripPathFromFile($dest)) {

        }

        // Copy remote url or file
        if ($this->isUrl($src) && $mime = $this->urlExists($src)) {

            if (!$newDirs = $this->mkdir($dest)) {
                return false;
            }

            if (is_dir($dest)) {
                $srcFileName = $this->stripPathFromFile($src);
                if ($srcFileName) {
                    $dest = $dest . '/' . $srcFileName;
                } else {
                    $dest = $dest . '/tmpfile.' . $this->extFromMime($mime);
                }
            }

            if (!$this->urlSaveToFile($src, $dest)) {
                foreach ($newDirs as $newDir) {
                    $this->delete($newDir);
                }
                return false;
            }

            return true;
        }

        return false;
    }

    private function stripTrailingSlashes($str)
    {
        return rtrim($str, '/');
    }

    private function stripPathFromFile($path)
    {
        preg_match('/^[\/\:\w\d]*\/([\w\d]*\.[\w\d]{2,})$/', $path, $match);
        return isset($match[1]) ? $match[1] : false;
    }

    private function isUrl($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL) ? true : false;
    }

    private function urlExists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        curl_exec($ch);

        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        return $mime;
    }

    private function urlSaveToFile($url, $dest)
    {
        $fp = fopen($dest, 'w');
        if (!$fp) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        curl_exec($ch);

        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);
        fclose($fp);

        return $mime;
    }

    private function extFromMime($mime)
    {
        preg_match('/(.*\-|\/)(\w+)/', $mime, $match);
        return isset($match[2]) ? $match[2] : false;
    }
}