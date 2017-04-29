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
    private $debugMode;

    /**
     * Connection constructor.
     * @param bool $debugMode
     */
    public function __construct($debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    /**
     * Add connection configuration
     * @param string $name
     * @param string $dialect
     * @param string $host
     * @param string $dbname
     * @param string $user
     * @param string $pswd
     * @param string $encoding
     * @param string $timezone
     */
    public function add($name, $dialect, $host, $dbname, $user, $pswd, $encoding, $timezone)
    {
        $this->conf[$name] = [$dialect, $host, $dbname, $user, $pswd, $encoding, $timezone];
    }

    /**
     * Return names of added connections
     * @return array
     */
    public function getConnectionNames()
    {
        $names = [];
        foreach ($this->conf as $name => $p) {
            $names[] = $name;
        }
        return $names;
    }

    /**
     * Return names of active connections
     * @return array
     */
    public function getActiveConnections()
    {
        $names = [];
        foreach ($this->conn as $name => $p) {
            $names[] = $name;
        }
        return $names;
    }

    /**
     * Return connection by its name
     * @param $name
     * @throws \Exception
     * @return \PDO
     */
    public function connect($name = null)
    {
        // If calling without connection name, try to get name of first connection
        if (!$name) {
            $connNames = $this->getConnectionNames();
            if (isset($connNames[0])) {
                $name = $connNames[0];
            } else {
                throw new \Exception('No available connection.');
            }

        // If calling with connection name, check if connection with this name exists
        } elseif (!isset($this->conf[$name])) {
            throw new \Exception('Connection with name \'' . $name . '\' does not exits.');
        }

        // Create new connection if does not exist
        if (!isset($this->conn[$name])) {
            $this->conn[$name] = new \PDO(
                $this->conf[$name][0] .
                ':host=' . $this->conf[$name][1] .
                ';dbname=' . $this->conf[$name][2],
                $this->conf[$name][3],
                $this->conf[$name][4]
            );

            // Set encoding
            $q = $this->conn[$name]->prepare('SET CHARACTER SET ?');
            $q->execute([$this->conf[$name][5]]);

            // Set time zone
            $q = $this->conn[$name]->prepare('SET time_zone = "?"');
            $q->execute([$this->conf[$name][6]]);

            // If required, set debug mode for this connection
            if ($this->debugMode) {
                $this->conn[$name]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
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