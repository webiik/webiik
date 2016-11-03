<?php
namespace Webiik;

class FileCache
{
    private $dir = '/';

    private $ext = '.cch';

    private $key;

    /**
     * Set cache storage directory
     * @param $dir
     */
    public function setDir($dir)
    {
        $this->dir = rtrim($dir) . '/';
    }

    /**
     * Set cache file extension
     * @param $ext
     */
    public function setExtension($ext)
    {
        $this->ext = '.' . trim($ext, '.');
    }

    /**
     * Store value by key with specified expiration
     * @param string $key
     * @param mixed $value
     * @param int $timestamp
     * @return bool
     */
    public function set($key, $value, $timestamp = 0)
    {
        $key = md5($key);

        $data = [
            'expires' => $timestamp,
            'data' => $value,
        ];

        if (file_put_contents($this->dir . $key . $this->ext, serialize($data)) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get stored value by key
     * @param string $key
     * @return mixed|bool
     */
    public function get($key)
    {
        $mdKey = $this->mdKey($key);;

        if (!file_exists($this->dir . $mdKey . $this->ext)) {
            return false;
        }

        $data = file_get_contents($this->dir . $mdKey . $this->ext);

        if ($data !== false) {

            $data = unserialize($data);

            if ($data['expires'] == 0 || $data['expires'] > time()) {
                return $data['data'];
            }

            $this->delete($key);
        }

        return false;
    }

    /**
     * Delete stored value by key
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        $key = $this->mdKey($key);

        if (!file_exists($this->dir . $key . $this->ext)) {
            return false;
        }

        return unlink($this->dir . $key . $this->ext);
    }

    /**
     * Return md5 hash for given $key
     * It caches already hashed keys into memory.
     * @param string $key
     * @return string
     */
    private function mdKey($key)
    {
        if (isset($this->key[$key])) {
            return $this->key[$key];
        }

        $this->key[$key] = md5($key);

        return $this->key[$key];
    }
}