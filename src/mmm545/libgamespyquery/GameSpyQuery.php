<?php

declare(strict_types=1);

namespace mmm545\libgamespyquery;

use function pack;
use function strlen;

/*
 * The reason behind this is that the byte order of packing an int to a binary string is
 * machine dependant, and pack() doesn't support a big endian int format. So in case the
 * byte order wasn't big endian i can just reverse the string and it would work.
 * Also don't ask me why i used `define` instead of `const`
 */

define('BIG_ENDIAN', pack("L", 1) === pack("N", 1));


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

    private $status;

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

        $this->status = explode("\x00\x00\x01\x70\x6C\x61\x79\x65\x72\x5F\x00\x00", $this->retrieveStatus($sessionId, (int)$challengeToken));
    }

    /**
     * @param $sessionId
     * @return string
     * @throws GameSpyQueryException
     */
    private function handshake($sessionId) : string{
        $command = pack("n", 65277);
        $command .= pack("c", 9);
        $command .= BIG_ENDIAN ? pack("i", $sessionId) : strrev(pack("i", $sessionId));
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
        $command .= BIG_ENDIAN ? pack("i", $sessionId) : strrev(pack("i", $sessionId));
        $command .= BIG_ENDIAN ? pack("i", $challengeToken) : strrev(pack("i", $challengeToken));
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
     * @return string|string[] The return can be either a single string or an array, depending on what data you want
     */
    //TODO: parse plugins list
    public function getValue(string $key){
        $data = explode("\x00", $this->status[0]);

        if($key === "players"){
            return explode("\x00", substr($this->status[1], 0, -2));
        }

        $pos = array_search($key, $data);
        if($pos !== false){
            return $data[$pos+1];
        }

        return "";
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