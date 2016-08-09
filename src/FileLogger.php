<?php
namespace Webiik;

/**
 * Class FileLogger
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class FileLogger extends Logger
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * FileLogger constructor.
     */
    public function __construct($dir, $file, $maxFileSize = 2, $maxTotalSize = 20)
    {
        $this->config['dir'] = rtrim($dir, '/');
        $this->config['file'] = $file;
        $this->config['maxFileSize'] = $maxFileSize;
        $this->config['maxTotalSize'] = $maxTotalSize;
    }

    /**
     * Log message to file and rotate logs if needed
     * @param $message
     */
    public function log($message)
    {
        file_put_contents($this->config['dir'] . '/' . $this->config['file'] . '.log', $message, FILE_APPEND);
        $this->rotate();
    }

    /**
     * Rotate or delete log if log size limit is exceeded
     */
    private function rotate()
    {
        $file = $this->config['dir'] . '/' . $this->config['file'] . '.log';
        $rotated = false;

        // Rotate
        if (filesize($file) / 1048576 > $this->config['maxFileSize']) {
            copy($file, $this->config['dir'] . '/' . $this->config['file'] . '.' . time() . '.log');
            unlink($file);
            $rotated = true;
        }

        // Delete
        if ($rotated) {
            $logTotalSize = 0;
            $handle = opendir($this->config['dir']);
            while (false !== ($entry = readdir($handle))) {
                if (!is_dir($entry) && strrpos($entry, $this->config['file']) !== false) {
                    if (!isset($oldestfile)) $oldestfile = $entry;
                    $logTotalSize += filesize($this->config['dir'] . '/' . $entry) / 1048576;
                }
            }

            if ($logTotalSize > $this->config['maxTotalSize'] && isset($oldestfile)) {
                unlink($this->config['dir'] . '/' . $oldestfile);
            }
        }
    }
}