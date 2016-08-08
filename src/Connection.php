<?php
namespace Webiik;

/**
 * Class Connection
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Connection
{
    private $conf = [];
    private $conn = [];

    /**
     * Add connection configuration
     * @param $name
     * @param $dialect
     * @param $host
     * @param $dbname
     * @param $user
     * @param $pswd
     * @param $encoding
     */
    public function add($name, $dialect, $host, $dbname, $user, $pswd, $encoding)
    {
        $this->conf[$name] = [$dialect, $host, $dbname, $user, $pswd, $encoding];
    }

    /**
     * Return connection by its name
     * @param $name
     * @throws \Exception
     * @return \PDO
     */
    public function connect($name)
    {
        if (!isset($this->conf[$name])) {
            throw new \Exception('Unknown connection name: ' . htmlspecialchars($name));
        }
        if (!isset($this->conn[$name])) {
            $this->conn[$name] = new \PDO(
                $this->conf[$name][0] .
                ':host=' . $this->conf[$name][1] .
                ';dbname=' . $this->conf[$name][2],
                $this->conf[$name][3],
                $this->conf[$name][4]
            );
            $q = $this->conn[$name]->prepare('SET CHARACTER SET ?');
            $q->execute([$this->conf[$name][5]]);
        }

        return $this->conn[$name];
    }

    /**
     * Unset connection by its name
     * @param $name
     */
    public function disconnect($name)
    {
        unset($this->conn[$name]);
    }
}