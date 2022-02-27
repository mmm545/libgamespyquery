<?php

declare(strict_types=1);

namespace mmm545\libgamespyquery;

use function pack;
use function strlen;
use function substr;
use function fread;

/**
 * A class used to query servers and get info from them.
 * Servers that don't have GS4 supported/enabled can't be queried
 * @package mmm545\libgamespyquery
 */
class GameSpyQuery
{

    /**
     * @var string $ip
     * IP to query
     */
    private string $ip;

    /**
     * @var int $port
     * Port to query
     */
    private int $port;

    /**
     * @var string $statusRaw
     * The raw status data
     */
    private string $statusRaw;

    /**
     * @param string $ip IP to query
     * @param int $port Port to query
     */
    public function __construct(string $ip, int $port, $statusRaw){
        $this->ip = $ip;
        $this->port = $port;
        $this->statusRaw = $statusRaw;
    }

    /**
     * Queries the destination IP and port.
     * @param int $timeout The connection timeout.
     * @throws GameSpyQueryException If the destination IP and port cannot be queried.
     */
    public static function query(string $ip, int $port, int $timeout = 2) : GameSpyQuery{
        $socket = @fsockopen('udp://'.$ip, $port, $errno, $errstr, $timeout);

        if($errno and $socket !== false) {
            fclose($socket);
            throw new GameSpyQueryException($errstr, $errno);
        }
        elseif($socket === false) {
            throw new GameSpyQueryException($errstr, $errno);
        }
        stream_set_timeout($socket, $timeout);
        $sessionId = rand();

        $challengeToken = self::handshake($socket, $sessionId);

        if(empty($challengeToken)){
            throw new GameSpyQueryException("Failed to retrieve challenge token"); //tbh i think i'm abusing exceptions
        }

        $statusRaw = self::retrieveStatus($socket, $sessionId, (int)$challengeToken);
        if(empty($statusRaw)){
            throw new GameSpyQueryException("Failed to retrieve server info");
        }
        return new self($ip, $port, $statusRaw);
    }

    /**
     * @param $sessionId
     * @return string|bool
     * @throws GameSpyQueryException
     */
    private static function handshake($socket, $sessionId){
        $command = pack("n", 65277);
        $command .= pack("c", 9);
        $command .= pack("N", $sessionId);
        $command .= pack("xxxx");

        $length = strlen($command);
        if ($length !== fwrite($socket, $command, $length)){
            throw new GameSpyQueryException("Failed to write to socket");
        }

        $response = fread($socket, 4096);

        if($response === false){
            throw new GameSpyQueryException("Failed to read from socket");
        }

        return substr($response, 5);
    }

    /**
     * @param int $sessionId
     * @param int $challengeToken
     * @return string|bool
     * @throws GameSpyQueryException
     */
    private static function retrieveStatus($socket, int $sessionId, int $challengeToken){
        $command = pack("n", 65277);
        $command .= pack("c", 0);
        $command .= pack("N", $sessionId);
        $command .= pack("N", $challengeToken);
        $command .= pack("xxxx");

        $length = strlen($command);
        if ($length !== fwrite($socket, $command, $length)){
            throw new GameSpyQueryException("Failed to write to socket");
        }

        $response = fread($socket, 4096);

        if($response === false){
            throw new GameSpyQueryException("Failed to read from socket");
        }

        return substr($response, 16);
    }

    /**
     * Gets data by its key
     * @param string $key The key of the data you want to get
     * @return string|string[]|bool The return can be either a string or an array, depending on what data you want to get. Returns false if the key can't be found
     */
    public function get(string $key){
        if(!isset($this->statusRaw)){
            return false;
        }

        $status = explode("\x00\x00\x01\x70\x6C\x61\x79\x65\x72\x5F\x00\x00", $this->statusRaw);
        $data = explode("\x00", $status[0]);

        switch($key){
            case "players":
                $players = substr($status[1], 0, -2);
                if($players === false){
                    return false;
                }
                return explode("\x00", $players);

            case "plugins":
                $plugins = $data[array_search("plugins", $data) + 1];
                $plugins = explode("; ", str_replace($this->get("server_engine").": ", "", $plugins));
                return $plugins;

            default:
                $pos = array_search($key, $data);
                if($pos !== false){
                    return $data[$pos + 1];
                }
                return false;

        }
    }

    /**
     * @return string The raw status response from the server
     */
    public function getStatusRaw(): string{
        return $this->statusRaw;
    }

    /**
     * @return string
     */
    public function getIp(): string{
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort(): int{
        return $this->port;
    }
}
