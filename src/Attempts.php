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
     * How many individual(action, ip, agent) attempts will be stored in database from now
     * @var array
     */
    private $storageLimit = ['attemptsCount' => 100, 'dateInterval' => 'PT24H'];

    /**
     * Dates of attempts will be set by this timezone identifier
     * @var string
     */
    private $timezone;

    /**
     * @var \PDO
     */
    private $db;

    public function __construct(\PDO $db, $timezone = null)
    {
        $this->timezone = $timezone ? $timezone : date('e');
        $this->db = $db;
    }

    /**
     * @desc see storageLimit
     * @param $attemptsCount
     * @param $dateInterval
     */
    public function setStorageLimit($attemptsCount, $dateInterval)
    {
        $this->storageLimit['attemptsCount'] = $attemptsCount;
        $this->storageLimit['dateInterval'] = $dateInterval;
    }

    /**
     * If storage limit is not exceeded, store individual(action, ip, agent) attempt in database.
     * Delete all (old) attempts outside the storage limit date interval.
     * @param $action
     * @return bool
     */
    public function setAttempt($action)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;

        if (rand(1, 100) <= 5) {
            $this->deleteOldAttempts();
        }

        if ($this->getAttemptsCount($action, $this->storageLimit['dateInterval']) < $this->storageLimit['attemptsCount']) {

            $date = new \DateTime('now', new \DateTimeZone($this->timezone));
            $date = $date->format('Y-m-d H:i:s');

            $q = $this->db->prepare('INSERT INTO attempts (ip, agent, action, date) VALUES (?, ?, ?, ?)');
            $q->execute([$ip, $agent, $action, $date]);
            return true;
        }

        return false;
    }

    /**
     * Return count of individual(action, ip, agent) attempts for specified action and date interval.
     * @param $action
     * @param null $dateInterval
     * @return int
     */
    public function getAttemptsCount($action, $dateInterval = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        $query = 'SELECT * FROM attempts WHERE action = ? AND ip = ?';
        $args = [$action, $ip];

        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;
        if ($agent) {
            $query .= ' AND agent = ?';
            $args[] = $agent;
        }

        if ($dateInterval) {
            $date = new \DateTime('now', new \DateTimeZone($this->timezone));
            $date->sub(new \DateInterval($dateInterval));
            $date = $date->format('Y-m-d H:i:s');

            $query .= ' AND date >= ?';
            $args[] = $date;
        }

        $q = $this->db->prepare($query);
        $q->execute($args);
        $attempts = $q->fetchAll();

        return count($attempts);
    }

    /**
     * Delete all (old) attempts outside the storage limit date interval.
     */
    private function deleteOldAttempts()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->timezone));
        $date->sub(new \DateInterval($this->storageLimit['dateInterval']));
        $date = $date->format('Y-m-d H:i:s');

        $q = $this->db->prepare('DELETE FROM attempts WHERE date < ?');
        $q->execute([$date]);
    }
}