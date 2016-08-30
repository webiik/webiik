<?php
namespace Webiik;

/**
 * Class Filesystem
 * Handle basic file operations with no pain
 *
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Filesystem
{
    // Todo: Atomic operations

    /**
     * Check if file, dir or url exist
     * On success return true
     * On error return false
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

    /**
     * Move uploaded file
     * On success return 'upfile_path' => [bool, 'final_file_path']
     * On error return message
     * @param $upfile
     * @param $dest
     * @param array $allowedMimeTypes
     * @param bool $isImage
     * @param int $maxSize
     * @return array|string
     */
    public function upload($upfile, $dest, $allowedMimeTypes = [], $isImage = false, $maxSize = 5242880)
    {
        if (!isset($_FILES[$upfile])) {
            return 'File does not exist.';
        }

        $destDir = $this->getPath($dest);

        if (!file_exists($destDir)) {
            return 'Destination directory does not exits.';
        }

        // Check $_FILES[$upfile]['error'] value
        switch ($_FILES[$upfile]['error']) {

            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_NO_FILE:
                return 'No file sent.';

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Exceeded php.ini filesize limit.';

            default:
                return 'Unknown errors.';
        }

        // Check file name length
        if (mb_strlen($_FILES[$upfile]['tmp_name'], 'utf-8') > 225) {
            return 'Source file name is too long.';
        }

        // You should also check filesize here.
        if ($_FILES[$upfile]['size'] > $maxSize) {
            return 'Exceeded filesize limit.';
        }

        // Check mime type and get file extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES[$upfile]['tmp_name']);
        finfo_close($finfo);
        foreach ($allowedMimeTypes as $allowedMime => $extension) {
            if ($mime == $allowedMime) {
                $ext = $extension;
                break;
            }
        }

        if (!isset($ext)) {
            return 'Unsupported file type.';
        }

        // Check if it is really image
        if ($isImage) {
            $size = @getimagesize($_FILES[$upfile]['tmp_name']);
            if (!is_array($size)) {
                return 'Unsupported file type.';
            }
        }

        // Calculate hash of uploaded file
        $uploadedFileHash = sha1_file($_FILES[$upfile]['tmp_name']);

        // Get destination filename
        if (!$destFile = $this->getFile($dest)) {
            $destFile = $_FILES[$upfile]['name'];
        }

        // Get destination filename withou extension
        preg_match('/(.*)\./', $destFile, $match);
        if (!isset($match[1])) {
            return 'Wrong file name.';
        }
        $destFile = $match[1];

        // Try to convert file name to ASCII
        // If conversion fails use hash as file name
        if (!$destFile = $this->encodeToAscii($destFile)) {
            $destFile = $uploadedFileHash;
        }

        // Check if file already exists
        // Detect duplicates (only for same destination dir and name)
        // Prepare destination file incl. path
        $destFile = $this->finalFile($destDir, $destFile, $ext, $uploadedFileHash);

        // Files are same do not upload anything
        if (!$destFile[0]) {
            return [$_FILES[$upfile]['tmp_name'] => $destFile];
        }

        // Upload file
        if (!@move_uploaded_file($_FILES[$upfile]['tmp_name'], $destFile[1])) {
            return $this->err();
        }

        return [$_FILES[$upfile]['tmp_name'] => $destFile];
    }

    // Todo: Zip source can be array|string with file(s) and dir(s)
    /**
     * Zip given file(s) to destination zip file
     * On success return array destination => [file1, file2, ...]
     * On error return string with error message
     * @param string|array $source
     * @param string $destination
     * @param int $permissions
     * @return array|string
     */
    public function zip($source, $destination, $permissions = 0777)
    {
        $zip = new \ZipArchive();
        $zipped = [];

        if (is_string($newDirs = $this->mkdir($this->getPath($destination), $permissions))) {
            // Err
            return $newDirs;
        }

        if ($zip->open($destination, \ZipArchive::CREATE) !== TRUE) {
            if (isset($newDirs[0])) $this->delete($newDirs[0]);
            return 'Cannot open zip file.';
        }

        if (is_string($source)) $source = [$source];

        foreach ($source as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, $this->getFile($file));
                $zipped[$destination][] = $file;
            } else {
                isset($newDirs[0]) ? $this->delete($newDirs[0]) : $this->delete($destination);
                return 'Source file does not exist.';
            }
        }

        $zip->close();

        return $zipped;
    }

    // Todo: Unzip source can be array|string with file(s) and dir(s)
    /**
     * Extract file to destination directory
     * On success return array $file => $destDir
     * On error return string with error message
     * @param $source
     * @param $destination
     * @param int $permissions
     * @return array|string
     */
    public function unzip($source, $destination, $deleteSource = false, $permissions = 0777)
    {
        $zip = new \ZipArchive();

        if ($zip->open($source) !== TRUE) {
            return 'Cannot open zip file.';
        }

        if (is_string($newDirs = $this->mkdir($destination, $permissions))) {
            // Err
            return $newDirs;
        }

        $zip->extractTo($destination);
        $zip->close();

        if($deleteSource){
            $this->delete($source);
        }

        return [$source => $destination];
    }

    /**
     * Move/Rename file or directory
     * On success return renamed file/dir 'src' => 'dest'
     * On error return message
     * @param $src
     * @param $dest
     * @return array|string
     */
    public function move($src, $dest)
    {
        if (is_file($src)) {
            if (!$this->getFile($dest)) {
                // Destination is dir
                $dest = $dest . '/' . $this->getFile($src);
            }
        }

        if (is_dir($src)) {
            if ($this->getFile($dest)) {
                return 'Destination must be a directory.';
            }
        }

        if (!@rename($src, $dest)) {
            return $this->err();
        }

        return [$src => $dest];
    }

    /**
     * Make given dir structure
     * On success return array of created dirs
     * On error return error message. All created dirs will be deleted.
     * @param $dir
     * @param int $permissions
     * @param null $context
     * @return array|string
     */
    public function mkdir($dir, $permissions = 0777, $context = null)
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

            if ($context) {
                $res = @mkdir($dirPath, $permissions, false, $context);
            } else {
                $res = @mkdir($dirPath, $permissions, false);
            }

            if (!$res) {
                if (isset($newDirs[0])) {
                    $this->delete($newDirs[0]);
                }
                return $this->err();
            }

            $newDirs[] = $dirPath;
        }

        if (count($newDirs) > 0) {
            return $newDirs;
        }

        return 'Got no dir structure.';
    }

    /**
     * Delete file or dir incl. sub dirs and files
     * On success return array of deleted dirs and files
     * On error return error message
     * @param $path
     * @param bool $keepDir Empty or delete given dir.
     * @param array $deleted
     * @return array|string
     */
    public function delete($path, $keepDir = false, $deleted = [])
    {
        if ($path == '') {
            return 'Got no file or dir.';
        }

        if (!file_exists($path)) {
            return 'Directory or file does not exist.';
        }

        if ($keepDir && is_bool($keepDir)) {
            $keepDir = 0;
        }
        $keepDir++;

        if (is_file($path)) {

            if (!@unlink($path)) {
                return $this->err();
            }

            $deleted[] = $path;
            return $deleted;
        }

        if (is_dir($path)) {

            $dirContent = glob(rtrim($path, '/') . '/*');

            foreach ($dirContent as $entry) {

                $deleted = $this->delete($entry, $keepDir, $deleted);

                if (is_string($deleted)) {
                    return $deleted;
                }
            }

            if ($keepDir !== 1) {
                if (!@rmdir($path)) {
                    return $this->err();
                }
                $deleted[] = $path;
            }

            return $deleted;
        }

        return $deleted;
    }

    /**
     * Copy file, dir(incl. sub dirs and files) or remote file(page) to destination file or folder
     * On success return array of copied dirs and files
     * On error return string with error message. All copied dirs and files will be deleted.
     *
     * You can copy:
     * file -> file
     * file -> dir
     * dir -> dir
     * remote file -> file
     * remote page -> file
     *
     * @param $src
     * @param $dest
     * @param null $context
     * @return bool
     */
    public function copy($src, $dest, $permissions = 0777, $context = null)
    {
        $src = rtrim($src, '/');
        $dest = rtrim($dest, '/');

        if ($this->isUrl($dest)) {
            return 'Destination must be local.';
        }

        // Copy local file
        if (is_file($src)) {

            if (is_string($newDirs = $this->mkdir($this->getPath($dest), $permissions))) {
                // Err
                return $newDirs;
            }

            if (is_dir($dest)) {
                $dest = $dest . '/' . $this->getFile($src);
            }

            if (!@copy($src, $dest, $context)) {
                if (isset($newDirs[0])) {
                    $this->delete($newDirs[0]);
                }
                return $this->err();
            }

            return [$src => $dest];
        }

        // Copy local dir
        if (is_dir($src)) {

            if ($this->getFile($dest)) {
                return 'Destination must be a directory.';
            }

            $cr = $this->copyDir($src, $dest, $permissions, $context);

            if (is_string($cr)) {
                $this->delete($dest);
            }

            return $cr;
        }

        // Copy remote url or file
        if ($this->isUrl($src) && $mime = $this->urlExists($src)) {

            if (is_string($newDirs = $this->mkdir($dest, $permissions))) {
                return $newDirs;
            }

            if (is_dir($dest)) {
                $srcFileName = $this->getFile($src);
                if ($srcFileName) {
                    $dest = $dest . '/' . $srcFileName;
                } else {
                    $dest = $dest . '/tmpfile.' . $this->extFromMime($mime);
                }
            }

            if (!$this->urlSaveToFile($src, $dest)) {
                if (isset($newDirs[0])) $this->delete($newDirs[0]);
                return 'Error during copying remote file.';
            }

            return [$src => $dest];
        }

        return false;
    }

    /**
     * Recursive dir copy
     * On success return array of copied dirs 'src' => 'dest'
     * On error return error message
     * @param $src
     * @param $dest
     * @param int $permissions
     * @param null $context
     * @param array $copied
     * @return array|string
     */
    private function copyDir($src, $dest, $permissions = 0777, $context = null, $copied = [])
    {
        // Name of copied dir
        preg_match('/.*\/([\w\-]*)/', $src, $match);
        $dirName = $match[1];

        // Create dest dir
        $dest = $dest . '/' . $dirName;
        if (is_string($newDirs = $this->mkdir($dest))) {
            // Err
            return $newDirs;
        }

        $copied[$src] = $dest;

        // Get content of source dir
        $dirContent = glob($src . '/*');
        foreach ($dirContent as $entry) {

            if (is_dir($entry)) {
                $this->copyDir($entry, $dest, $permissions, $context, $copied);
            }

            if (is_file($entry)) {

                $dest = $dest . '/' . $this->getFile($entry);

                if ($context) {
                    $cr = @copy($entry, $dest, $context);
                } else {
                    $cr = @copy($entry, $dest);
                }

                if (!$cr) {
                    return $this->err();
                }

                $copied[$entry] = $dest;
            }
        }

        return $copied;
    }

    /**
     * On success return path without trailing slash
     * On error return false
     * @param $path
     * @return string|bool
     */
    private function getPath($path)
    {
        if (!is_string($path) || $path == '') {
            return false;
        }

        // Get path and root from stream path
        if ($this->isUrl($path)) {

            $urlParts = parse_url($path);


            if (isset($urlParts['path'])) {
                $path = $urlParts['path'];
            } else {
                $path = false;
            }

            $uri = $urlParts['scheme'] . '://' . $urlParts['host'];

        }

        // Process path
        if ($path) {
            $path = pathinfo($path);

            if (isset($path['extension'])) {
                $path = $path['dirname'];
            } else {
                $path = $path['basename'] ? $path['dirname'] . '/' . $path['basename'] : $path['dirname'];
            }
        }

        // Complete stream path
        if (isset($uri)) {
            $path = $uri . $path;
        }

        $path = rtrim($path, '/');

        return $path;
    }

    /**
     * On sucesss return file name with extension
     * On error return false
     * @param $path
     * @return string|bool
     */
    private function getFile($path)
    {
        if (!is_string($path) || $path == '') {
            return false;
        }

        // Get file from stream path
        if ($this->isUrl($path)) {

            // Stream
            $urlParts = parse_url($path);

            if (isset($urlParts['path'])) {
                preg_match('/\/([^\/]*\.\w{1,}$)/', $urlParts['path'], $match);
            }

            return isset($match[1]) ? $match[1] : false;

        }

        // Get file from path
        $path = pathinfo($path);
        return isset($path['extension']) ? $path['filename'] . '.' . $path['extension'] : false;
    }

    /**
     * Check if given string is valid URL
     * On success return true
     * On error return false
     * @param $str
     * @return bool
     */
    private function isUrl($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * Check if remote file exists
     * On success return mime type of copied file
     * On error return false
     * Notice: Don't trust mime type. It's taken from http header so it can be easily spoofed.
     * @param $url
     * @return mixed
     */
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

    /**
     * Copy remote file to local file
     * On success return mime type of copied file
     * On error return false
     * Notice: Don't trust mime type. It's taken from http header so it can be easily spoofed.
     * @param $url
     * @param $dest
     * @return bool|mixed
     */
    private function urlSaveToFile($url, $dest)
    {
        $fp = @fopen($dest, 'w');
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

    /**
     * Return second part of mime without dash specs
     * @param $mime
     * @return bool
     */
    private function extFromMime($mime)
    {
        preg_match('/(.*\-|\/)(\w+)/', $mime, $match);
        return isset($match[2]) ? $match[2] : false;
    }

    /**
     * Get and return last php error message
     * @return string
     */
    private function err()
    {
        $err = error_get_last();
        return $err['message'] . ' in <b>' . $err['file'] . '</b> on line <b>' . $err['line'] . '</b>';
    }

    /**
     * On successful encoding return ascii string
     * On error return false
     * @param $string
     * @return bool|mixed
     */
    private function encodeToAscii($string)
    {
        $string = @iconv('utf-8', 'US-ASCII//TRANSLIT', $string);
        return $string ? preg_replace('~[^a-zA-Z0-9\-\_\.\/]+~', '', $string) : false;
    }

    /**
     * Compare uploaded file with stored file
     * If stored file exists and is different from uploaded file,
     * add count to uploaded file name.
     * Retrun path of file to store array [bool(0 = same, 1 = different), $filePath]
     * @param $dir
     * @param $file
     * @param $ext
     * @param $uploadedFileHash
     * @return array
     */
    private function finalFile($dir, $file, $ext, $uploadedFileHash)
    {
        $storedFile = $dir . '/' . $file . '.' . $ext;

        $i = 0;
        while (file_exists($storedFile)) {

            $destFileHash = sha1_file($storedFile);

            if ($destFileHash == $uploadedFileHash) {
                return [false, $storedFile];
            }

            $i++;
            $storedFile = $dir . '/' . $file . '-' . $i . '.' . $ext;
        }

        return [true, $storedFile];
    }
}