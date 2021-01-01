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
    private $ip;

    /**
     * @var int $port
     * Port to query
     */
    private $port;
    
    private $socket;

    private $statusRaw;

    /**
     * @param string $ip IP to query
     * @param int $port Port to query
     */
    public function __construct(string $ip, int $port){
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * Queries the destination IP and port.
     * @param int $timeout The connection timeout
     * @throws GameSpyQueryException If the destination IP and port cannot be queried
     */
    public function query(int $timeout = 2){
        $this->socket = @fsockopen('udp://'.$this->ip, $this->port, $errno, $errstr, $timeout);

        if($errno and $this->socket !== false) {
            fclose($this->socket);
            throw new GameSpyQueryException($errstr, $errno);
        }
        elseif($this->socket === false) {
            throw new GameSpyQueryException($errstr, $errno);
        }
        stream_set_timeout($this->socket, $timeout);
        $sessionId = rand();

        $challengeToken = $this->handshake($sessionId);

        if(empty($challengeToken)){
            throw new GameSpyQueryException("Response is empty"); //tbh i think i'm abusing exceptions
        }

        $this->statusRaw = $this->retrieveStatus($sessionId, (int)$challengeToken);
    }

    /**
     * @param $sessionId
     * @return string
     * @throws GameSpyQueryException
     */
    private function handshake($sessionId) : string{
        $command = pack("n", 65277);
        $command .= pack("c", 9);
        $command .= pack("N", $sessionId);
        $command .= pack("xxxx");

        $length = strlen($command);
        if ($length !== fwrite($this->socket, $command, $length)){
            throw new GameSpyQueryException("Failed to write to socket");
        }

        $response = fread($this->socket, 4096);

        if($response === false){
            throw new GameSpyQueryException("Failed to read from socket");
        }

        return substr($response, 5);
    }

    /**
     * @param int $sessionId
     * @param int $challengeToken
     * @return string
     * @throws GameSpyQueryException
     */
    private function retrieveStatus(int $sessionId, int $challengeToken) : string{
        $command = pack("n", 65277);
        $command .= pack("c", 0);
        $command .= pack("N", $sessionId);
        $command .= pack("N", $challengeToken);
        $command .= pack("xxxx");

        $length = strlen($command);
        if ($length !== fwrite($this->socket, $command, $length)){
            throw new GameSpyQueryException("Failed to write to socket");
        }

        $response = fread($this->socket, 4096);

        if($response === false){
            throw new GameSpyQueryException("Failed to read from socket");
        }

        return substr($response, 16);
    }

    /**
     * Gets data by it's key
     * @param string $key The key of the data you want to get
     * @return string|string[] The return can be either a string or an array, depending on what data you want to get. Returns false if the key can't be found
     */
    public function get(string $key){
        if(!isset($this->statusRaw)){
            return false;
        }

        $status = explode("\x00\x00\x01\x70\x6C\x61\x79\x65\x72\x5F\x00\x00", $this->statusRaw);
        $data = explode("\x00", $status[0]);

        switch($key){
            case "players":
                return explode("\x00", substr($status[1], 0, -2));

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
     * @return string The raw status response from the server, or false if the status data is null
     */
    public function getStatusRaw(){
        return isset($this->statusRaw) ? $this->statusRaw : false;
    }

    /**
     * @return string
     */
    public function getIp(): string{
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void{
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getPort(): int{
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void{
        $this->port = $port;
    }
}