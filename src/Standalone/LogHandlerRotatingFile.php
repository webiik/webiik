<?php

namespace Webiik;

/**
 * Class LogHandlerRotatingFile
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class LogHandlerRotatingFile implements LogHandlerInterface
{
    /**
     * Log file dir
     * @var string
     */
    private $dir;

    /**
     * Log file name
     * @var
     */
    private $fileName;


    /**
     * Log file extension
     * @var string
     */
    private $fileExtension;

    /**
     * Maximal size of individual log file in kb
     * @var int
     */
    private $maxFileSize;

    /**
     * Maximal size of all log files in log dir in kb
     * If this limit is exceeded, oldest log file will be deleted
     * @var int
     */
    private $maxLogSize;

    public function __construct($dir, $fileName, $fileExtension = 'log', $maxFileSize = 512, $maxLogSize = 10240)
    {
        $this->dir = rtrim($dir, '/');
        $this->fileName = $fileName;
        $this->fileExtension = trim($fileExtension, '.');
        $this->maxFileSize = $maxFileSize;
        $this->maxLogSize = $maxLogSize;

    }

    /**
     * Write message to log file and rotate/delete log file when size limit is exceeded
     * @param $data
     */
    public function write($data)
    {
        $file = $this->getFilePath();

        file_put_contents($file, json_encode($data) . "\r\n", FILE_APPEND);

        if ($this->rotate()) {
            $this->deleteOldestLogFile();
        }
    }

    /**
     * Rotate log file when log file size is exceeded
     * @return bool
     */
    private function rotate()
    {
        $rotated = false;

        $file = $this->getFilePath();

        if (filesize($file) / 1024 > $this->maxFileSize) {
            $lastDotPos = strrpos($file, '.');
            $p1 = substr($file, 0, $lastDotPos);
            $p2 = substr($file, $lastDotPos, strlen($file));
            $backupFile = $p1 . '.' . time() . $p2;
            copy($file, $backupFile);
            unlink($file);
            $rotated = true;
        }

        return $rotated;
    }

    /**
     * Delete oldest log file when max log size is exceeded
     * @return bool
     */
    private function deleteOldestLogFile()
    {
        $deleted = false;

        $logTotalSize = 0;

        $dh = opendir($this->dir);

        while (($entry = readdir($dh)) !== false) {

            if(preg_match('/^'.preg_quote($this->fileName).'/', $entry))

                if (!isset($oldestfile)) $oldestfile = $entry;
                $logTotalSize += filesize($this->dir . '/' . $entry);
        }

        closedir($dh);

        if ($logTotalSize / 1024 > $this->maxLogSize && isset($oldestfile)) {
            unlink($this->dir . '/' . $oldestfile);
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * @return string
     */
    private function getFilePath()
    {
        return $this->dir . '/' . $this->fileName . '.' . $this->fileExtension;
    }
}