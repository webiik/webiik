<?php
namespace Webiik;

/**
 * Class Attempts
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Attempts
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Store individual(action, ip, agent) attempt in database
     * @param $action
     */
    public function setAttempt($action)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $q = $this->connection->connect()->prepare('INSERT INTO attempts (ip, agent, action, timestamp) VALUES (?, ?, ?, UNIX_TIMESTAMP())');
        $q->execute([$ip, $agent, $action]);
    }

    /**
     * Return count of individual(action, ip, agent) attempts for specified action and date interval
     * @param string $action
     * @param int $sec
     * @param bool $checkAgent
     * @return int
     */
    public function getAttemptsCount($action, $sec = 0, $checkAgent = false)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        $query = 'SELECT * FROM attempts WHERE action = ? AND ip = ?';
        $args = [$action, $ip];

        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;
        if ($checkAgent && $agent) {
            $query .= ' AND agent = ?';
            $args[] = $agent;
        }

        if ($sec > 0) {
            $query .= ' AND timestamp >= ?';
            $args[] = time() - $sec;
        }

        $q = $this->connection->connect()->prepare($query);
        $q->execute($args);
        $attempts = $q->fetchAll();

        return count($attempts);
    }

    /**
     * Delete all attempts or just attempts for specified action or date interval
     * @param null $action
     * @param int $sec
     * @return int
     */
    public function deleteAttempts($action = null, $sec = 0)
    {
        $query = 'DELETE FROM attempts';
        $args = [];

        if ($action) {
            $query .= ' WHERE action = ?';
            $args[] = $action;
        }

        if ($sec > 0) {
            $query .= $action ? ' AND timestamp < ?' : ' WHERE timestamp < ?';
            $args[] = time() - $sec;
        }

        $q = $this->connection->connect()->prepare($query);
        $q->execute($args);

        return $q->rowCount();
    }
}