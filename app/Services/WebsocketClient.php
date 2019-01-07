<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 07.01.19
 * Time: 1:38
 */

namespace App\Services;


use App\Console\Commands\SocketServer;

class WebsocketClient {

    private $_Socket = null;

    public function __construct($host, $port) {
        $this->_connect($host, $port);
    }

    public function __destruct() {
        $this->_disconnect();
    }

    public function sendData($data) {
        // send actual data:
        $encoded = SocketServer::encode($data, 'text', true);
        fwrite($this->_Socket, $encoded) or die('Error:' . $errno . ':' . $errstr);
        $wsData = fread($this->_Socket, 2000);
        $retData = trim($wsData, "\x00\xff");
        return $retData;
    }

    private function _connect($host, $port) {
        $key1 = $this->_generateRandomString(32);

        $header = "GET /echo HTTP/1.1\r\n";
        $header.= "Host: " . $host . ":" . $port . "\r\n";
        $header.= "Upgrade: websocket\r\n";
        $header.= "Connection: Upgrade\r\n";
        $header.= "Sec-WebSocket-Key: " . $key1 . "\r\n";
        $header.= "Sec-WebSocket-Protocol: chat, superchat\r\n";
        $header.= "Sec-WebSocket-Version: 13\r\n";
        $header.= "Origin: http://localhost\r\n";
        $header.= "\r\n";


        $this->_Socket = fsockopen($host, $port, $errno, $errstr, 2);
        fwrite($this->_Socket, $header) or die('Error: ' . $errno . ':' . $errstr);
        $response = fread($this->_Socket, 2000);

        /**
         * @todo: check response here. Currently not implemented cause "2 key handshake" is already deprecated.
         * See: http://en.wikipedia.org/wiki/WebSocket#WebSocket_Protocol_Handshake
         */
        return true;
    }

    private function _disconnect() {
        fclose($this->_Socket);
    }

    private function _generateRandomString($length = 10, $addSpaces = true, $addNumbers = true) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars = array();
        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }
        // add spaces and numbers:
        if ($addSpaces === true) {
            array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
        }
        if ($addNumbers === true) {
            array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        }
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, $length);
        return $randomString;
    }

}