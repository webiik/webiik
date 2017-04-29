<?php
namespace Webiik;

/**
 * Class Filesystem
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
     * Return final name for $fileOne
     * Check if $fileOne exists and if exists check if is same like $fileTwo.
     * If $fileOne does not exist return [1, fileOne.ext]
     * If $fileOne exists and is same like $fileTwo return [0, fileOne.ext]
     * If $fileOne exists and is not same like $fileTwo return [1, fileOne-count.ext]
     * @param $fileOne
     * @param $fileTwo
     * @return array
     */
    public function getComparedName($fileOne, $fileTwo)
    {
        $fileTwoHash = sha1_file($fileTwo);
        $fileOneDir = $this->getPath($fileOne);
        $fileOneName = $this->getFileName($fileOne);
        $fileOneExt = $this->getFileExt($fileOne);

        $i = 0;
        while (file_exists($fileOne)) {

            $fileOneHash = sha1_file($fileOne);

            if ($fileOneHash == $fileTwoHash) {
                return [false, $fileOne];
            }

            $i++;
            $fileOne = $fileOneDir . '/' . $fileOneName . '-' . $i . '.' . $fileOneExt;
        }

        return [true, $fileOne];
    }

    /**
     * Return ascii string on success, otherwise false
     * @param $string
     * @return bool|mixed
     */
    public function convToAscii($string)
    {
        $string = @iconv('utf-8', 'US-ASCII//TRANSLIT', $string);
        return $string ? preg_replace('~[^a-zA-Z0-9\-\_\.\/]+~', '', $string) : false;
    }

    /**
     * Return path without filename and trailing slash on success, otherwise false.
     * @param $path
     * @return string|bool
     */
    public function getPath($path)
    {
        // Process path
        $path = pathinfo($path);

        if (isset($path['extension'])) {
            $path = $path['dirname'];
        } else if (isset($path['basename'])) {
            $path = $path['basename'] ? $path['dirname'] . '/' . $path['basename'] : $path['dirname'];
        }

        return isset($path) ? rtrim($path, '/') : false;
    }

    /**
     * Return file name with extension on success, otherwise false
     * @param $path
     * @return string|bool
     */
    public function getFile($path)
    {
        // Get file from path
        $path = pathinfo($path);
        return isset($path['extension']) ? $path['filename'] . '.' . $path['extension'] : false;
    }

    /**
     * Return file name without extension on success, otherwise false
     * @param $path
     * @return string|bool
     */
    public function getFileName($path)
    {
        $path = pathinfo($path);
        return isset($path['filename']) ? $path['filename'] : false;
    }

    /**
     * Return file's extension on success, otherwise false
     * @param $path
     * @return string|bool
     */
    public function getFileExt($path)
    {
        $path = pathinfo($path);
        return isset($path['extension']) ? $path['extension'] : false;
    }

    /**
     * Return file mime on success, otherwise false
     * @param $path
     * @return bool|string
     */
    public function getFileMime($path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return $mime;
    }

    /**
     * Return second part of mime without dash specs
     * @param $mime
     * @return bool|string
     */
    public function getExtFromMime($mime)
    {
        preg_match('/(.*\-|\/)(\w+)/', $mime, $match);
        return isset($match[2]) ? $match[2] : false;
    }

    /**
     * Return true if file is image, otherwise false
     * @param $path
     * @return bool
     */
    public function isImage($path)
    {
        $size = @getimagesize($path);
        return is_array($size) ? true : false;
    }

    /**
     * Move uploaded file
     * On success return array [bool(0-uploaded file already exists, 1-new file), 'final_file_path']
     * On error return message
     * @param $inputName - Name of input[type=file] form field
     * @param $destDir - Dir where file will be uploaded
     * @param $destFile - Destination file name without extension. If it's not set then name of uploaded file will be used.
     * @param array $allowedMimeTypes - Key value array of allowed files eg. ['image/jpeg' => 'jpg']
     * @param bool $isImage - Additional check if file is image.
     * @param bool $maxSize - Max file size in bytes.
     * @return array|string
     */
    public function upload($inputName, $allowedMimeTypes = [], $destDir, $destFile = null, $maxSize = false, $isImage = false)
    {
        $destDir = '/' . trim($destDir, '/');

        // Check if file exists
        if (!isset($_FILES[$inputName])) {
            return 'File does not exist.';
        }

        // Check if destination dir exists
        if (!file_exists($destDir)) {
            return 'Destination directory does not exits.';
        }

        // Check $_FILES[$upfile]['error']
        switch ($_FILES[$inputName]['error']) {

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
        if (mb_strlen($_FILES[$inputName]['name'], 'utf-8') > 225) {
            return 'Source file name is too long.';
        }

        // Check filesize here
        if ($maxSize && $_FILES[$inputName]['size'] > $maxSize) {
            return 'Exceeded filesize limit.';
        }

        // Check mime type and get file extension
        $mime = $this->getFileMime($_FILES[$inputName]['tmp_name']);
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
        if ($isImage && !$this->isImage($_FILES[$inputName]['tmp_name'])) {
            return 'Unsupported file type.';
        }

        // Get destination filename without extension
        $destFile = $destFile ? $destFile : $this->getFileName($_FILES[$inputName]['name']);

        // Try to convert destination filename name to ASCII
        // If conversion fails use hash of file as file name
        if (!$destFile = $this->convToAscii($destFile)) {
            $destFile = sha1_file($_FILES[$inputName]['tmp_name']);
        }

        // Finalise destination file
        $destFile = $destDir . '/' . $destFile . '.' . $ext;

        // Check if file already exists
        // Detect duplicates (only for same destination dir and name)
        // Prepare destination file incl. path
        $destFile = $this->getComparedName($destFile, $_FILES[$inputName]['tmp_name']);
        $destFile[] = $this->getFile($destFile[1]);

        // Files are same do not upload anything
        if (!$destFile[0]) {
            $this->delete($_FILES[$inputName]['tmp_name']);
            return $destFile;
        }

        // Upload file
        if (!@move_uploaded_file($_FILES[$inputName]['tmp_name'], $destFile[1])) {
            return 'Error during move_uploaded_file fn.';
        }

        return $destFile;
    }

    /**
     * Todo: Zip source can be array|string with file(s) and dir(s)
     * Zip given file(s) to destination zip file
     * On success return array of zipped files
     * On error return false
     * @param string|array $source
     * @param string $destination
     * @param int $permissions
     * @return array|string
     */
    public function zip($source, $destination, $permissions = 0777)
    {
        $zip = new \ZipArchive();
        $zipped = [];

        // File must be the zip file
        if (!$this->getFileExt($destination) != 'zip') {
            $zip->close();
            return false;
        }

        // Prepare dirs
        if (!$newDirs = $this->mkdir($this->getPath($destination), $permissions)) {
            $zip->close();
            return false;
        }

        // Try to create archive
        if ($zip->open($destination, \ZipArchive::CREATE) !== TRUE) {
            if (isset($newDirs[0])) $this->delete($newDirs[0]);
            $zip->close();
            return false;
        }

        // Add files to zip
        if (is_string($source)) $source = [$source];
        foreach ($source as $file) {
            if ($zip->addFile($file, $this->getFile($file))) {
                $zipped[] = $file;
            } else {
                isset($newDirs[0]) ? $this->delete($newDirs[0]) : $this->delete($destination);
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return $zipped;
    }

    /**
     * Todo: Unzip source can be array|string with file(s) and dir(s)
     * Extract file to destination directory
     * On success return array $file => $destDir
     * On error return string with error message
     * @param $source
     * @param $destination
     * @param int $permissions
     * @return array|string
     */
    public function unzip($source, $destination, $permissions = 0777)
    {
        $zip = new \ZipArchive();

        // Open archive
        if ($zip->open($source) !== TRUE) {
            return false;
        }

        // Prepare dirs
        if (!$newDirs = $this->mkdir($destination, $permissions)) {
            $zip->close();
            return false;
        }

        // Extract zip
        if (!$zip->extractTo($destination)) {
            if (isset($newDirs[0])) $this->delete($newDirs[0]);
            $zip->close();
            return false;
        }

        $zip->close();
        return true;
    }

    /**
     * Move/Rename file or directory
     * On success return renamed file/dir 'src' => 'dest'
     * On error return message
     * @param $source
     * @param $destination
     * @return bool
     */
    public function move($source, $destination)
    {
        // If source is file and destination is dir add src file to destination path
        if (is_file($source) && !$this->getFile($destination)) {
            $destination = $destination . '/' . $this->getFile($source);
        }

        // If source is dir and destination is file return error
        if (is_dir($source) && $this->getFile($destination)) {
            return false;
        }

        if (!@rename($source, $destination)) {
            return false;
        }

        return true;
    }

    /**
     * Make given dir structure on success
     * Return array of created dirs on success, otherwise false.
     * @param $dir
     * @param int $permissions
     * @param null $context
     * @return array|bool
     */
    public function mkdir($dir, $permissions = 0777, $context = null)
    {
        $newDirs = [];

        // If dir already exists return empty array
        if (file_exists($dir)) {
            return $newDirs;
        }

        // Explode dir path and try create dir one by one
        $dirPath = '';
        $dirs = explode('/', trim($dir, '/'));
        foreach ($dirs as $dir) {

            $dirPath .= '/' . $dir;

            // Dir already exists so skip it
            if (file_exists($dirPath)) {
                continue;
            }

            // Try to create new dir
            if ($context) {
                $res = @mkdir($dirPath, $permissions, false, $context);
            } else {
                $res = @mkdir($dirPath, $permissions, false);
            }

            // If creation of new dir fails, delete created dirs and return false
            if (!$res) {
                if (isset($newDirs[0])) {
                    $this->delete($newDirs[0]);
                }
                return false;
            }

            // Add newly created dir
            $newDirs[] = $dirPath;
        }

        // If some dirs were created return array with all created dirs
        if (count($newDirs) > 0) {
            return $newDirs;
        }

        // Got no dir structure
        return false;
    }

    /**
     * Delete single file or complete dir with sub dirs and files
     * Return array of deleted dirs and files on success, otherwise false
     * @param $path
     * @param bool $keepDir Empty or delete given dir.
     * @param array $deleted
     * @return array|string
     */
    public function delete($path, $keepDir = false, $deleted = [])
    {
        // Directory or file does not exist
        if (!file_exists($path)) {
            return false;
        }

        if ($keepDir && is_bool($keepDir)) {
            $keepDir = 0;
        }
        $keepDir++;

        // Try to delete file
        if (is_file($path)) {

            if (!@unlink($path)) {
                return false;
            }

            $deleted[] = $path;
        }

        // Try to delete dir and its content
        if (is_dir($path)) {

            $dirContent = glob(rtrim($path, '/') . '/*');

            foreach ($dirContent as $entry) {

                $deleted = $this->delete($entry, $keepDir, $deleted);

                if (!$deleted) {
                    return $deleted;
                }
            }

            // Try to delete dir
            if ($keepDir !== 1) {
                if (!@rmdir($path)) {
                    return false;
                }
                $deleted[] = $path;
            }
        }

        return $deleted;
    }

    /**
     * Copy file or dir(incl. sub dirs and files) to destination file or dir on success
     * Return array of copied dirs and files on success, otherwise false
     *
     * You can copy:
     * file -> file, file -> dir, dir -> dir
     *
     * @param $source
     * @param $destination
     * @param int $permissions
     * @param null $context
     * @return bool|array
     */
    public function copy($source, $destination, $permissions = 0777, $context = null)
    {
        // Copy local file
        if (is_file($source)) {

            // Prepare dirs
            if (!$newDirs = $this->mkdir($this->getPath($destination), $permissions)) {
                return $newDirs;
            }

            // If source is file and destination is dir add src file to destination path
            if (is_dir($destination)) {
                $destination = $destination . '/' . $this->getFile($source);
            }

            // Try to copy source file to destination
            if ($context) {
                $cr = @copy($source, $destination, $context);
            } else {
                $cr = @copy($source, $destination);
            }

            // If copy fails delete all newly created dirs
            if (!$cr) {
                if (isset($newDirs[0])) {
                    $this->delete($newDirs[0]);
                }
                return false;
            }

            return [$source => $destination];
        }

        // Copy local dir
        if (is_dir($source)) {

            // If source is dir and destination is file return error
            if ($this->getFile($destination)) {
                return false;
            }

            // Recursive copy dir
            $cr = $this->copyDir($source, $destination, $permissions, $context);

            return $cr;
        }

        return false;
    }

    /**
     * Recursive dir copy
     * On success return array of copied dirs 'src' => 'dest'
     * On error return false
     * @param $src
     * @param $dest
     * @param int $permissions
     * @param null $context
     * @param array $copied
     * @param array $addedDirs
     * @return array|bool
     */
    private function copyDir($src, $dest, $permissions = 0777, $context = null, $copied = [], $addedDirs = [])
    {
        // Get name of copied dir
        preg_match('/.*\/([\w\-]*)/', $src, $match);
        $dirName = $match[1];

        // Try to create dest dir
        $dest = $dest . '/' . $dirName;
        if (!$newDirs = $this->mkdir($dest)) {

            // On error delete all copied files and dirs and return false
            foreach ($copied as $record) {
                if (is_file($record)) {
                    $this->delete($record);
                }
            }

            if (isset($addedDirs[0])) {
                $this->delete($addedDirs[0]);
            }

            return false;
        }

        // Store only newly created dir, we will use it in case of error for deleting copied dirs
        // It prevents delete dir that was already created before copying inside that dir
        $addedDirs = array_merge($addedDirs, $newDirs);

        // Store copied dir to array, just for return value
        $copied[$src] = $dest;

        // Get content of source dir
        $dirContent = glob($src . '/*');
        foreach ($dirContent as $entry) {

            // Try to copy dir
            if (is_dir($entry)) {
                if (!$this->copyDir($entry, $dest, $permissions, $context, $copied, $addedDirs)) {
                    return false;
                }
            }

            // Try to copy file
            if (is_file($entry)) {

                $dest = $dest . '/' . $this->getFile($entry);

                if ($context) {
                    $cr = @copy($entry, $dest, $context);
                } else {
                    $cr = @copy($entry, $dest);
                }

                if (!$cr) {

                    // On error delete all copied files and dirs and return false
                    foreach ($copied as $record) {
                        if (is_file($record)) {
                            $this->delete($record);
                        }
                    }

                    if (isset($addedDirs[0])) {
                        $this->delete($addedDirs[0]);
                    }

                    return false;
                }

                // Store copied dir to array, just for return value
                $copied[$entry] = $dest;
            }
        }

        return $copied;
    }
}