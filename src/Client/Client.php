<?php


namespace Vladitot\Nats\Client;

use RandomLib\Factory;
use RandomLib\Generator;

use Exception;

class Client
{

    private $messagesBackpack = [];
    /**
     * @var int
     * для отслеживания времени последнего отправленного сообщения
     */
    private $lastSentMessageTime = 0;
    /**
     * @var int
     * для прерывания времени ожидания сообщения
     */
    private $waitingMessageTimeout = 1;
    /**
     * @var int $lastPingTime
     *
     * Last time, when ping was
     *
     * We should ping connection for keep-alive
     * Or socket is falling down without exception.
     */
    private $lastPingTime = 0;
    /**
     * Show DEBUG info?
     *
     * @var boolean $debug If debug is enabled.
     */
    private $debug = false;
    /**
     * Enable or disable debug mode.
     *
     * @param boolean $debug If debug is enabled.
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }
    /**
     * Number of PINGs.
     *
     * @var integer number of pings.
     */
    private $pings = 0;
    /**
     * Return the number of pings.
     *
     * @return integer Number of pings
     */
    public function pingsCount()
    {
        return $this->pings;
    }
    /**
     * Chunk size in bytes to use when reading an stream of data.
     *
     * @var integer size of chunk.
     */
    private $chunkSize = 1500;
    /**
     * Number of messages published.
     *
     * @var int number of messages
     */
    private $pubs = 0;
    /**
     * Return the number of messages published.
     *
     * @return integer number of messages published
     */
    public function pubsCount()
    {
        return $this->pubs;
    }
    /**
     * Number of reconnects to the server.
     *
     * @var int Number of reconnects
     */
    private $reconnects = 0;
    /**
     * Return the number of reconnects to the server.
     *
     * @return integer number of reconnects
     */
    public function reconnectsCount()
    {
        return $this->reconnects;
    }
    /**
     * List of available subscriptions.
     *
     * @var array list of subscriptions
     */
    private $subscriptions = [];
    /**
     * Return the number of subscriptions available.
     *
     * @return integer number of subscription
     */
    public function subscriptionsCount()
    {
        return count($this->subscriptions);
    }
    /**
     * Return subscriptions list.
     *
     * @return array list of subscription ids
     */
    public function getSubscriptions()
    {
        return array_keys($this->subscriptions);
    }
    /**
     * Connection options object.
     *
     * @var ConnectionOptions|null
     */
    private $options = null;
    /**
     * Connection timeout
     *
     * @var float
     */
    private $timeout = 30;
    /**
     * Stream File Pointer.
     *
     * @var mixed Socket file pointer
     */
    private $streamSocket;
    /**
     * Generator object.
     *
     * @var Generator|Php71RandomGenerator
     */
    private $randomGenerator;
    /**
     * Sets the chunck size in bytes to be processed when reading.
     *
     * @param integer $chunkSize Set byte chunk len to read when reading from wire.
     *
     * @return void
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }
    /**
     * Set Stream Timeout.
     *
     * @param float $seconds Before timeout on stream.
     *
     * @return boolean
     */
    public function setStreamTimeout($seconds)
    {
        if ($this->isConnected() === true) {
            if (is_numeric($seconds) === true) {
                try {
                    return stream_set_timeout($this->streamSocket, $seconds);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }
    /**
     * Returns an stream socket for this connection.
     *
     * @return resource
     */
    public function getStreamSocket()
    {
        return $this->streamSocket;
    }
    /**
     * Indicates whether $response is an error response.
     *
     * @param string $response The Nats Server response.
     *
     * @return boolean
     */
    private function isErrorResponse($response)
    {
        return substr($response, 0, 4) === '-ERR';
    }
    /**
     * Checks if the client is connected to a server.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return isset($this->streamSocket);
    }
    /**
     * Returns an stream socket to the desired server.
     *
     * @param string $address Server url string.
     * @param float  $timeout Number of seconds until the connect() system call should timeout.
     *
     * @return resource
     * @throws \Exception Exception raised if connection fails.
     */
    private function getStream($address, $timeout)
    {
        $errno  = null;
        $errstr = null;
        set_error_handler(
            function () {
                return true;
            }
        );
        $fp = stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        stream_set_blocking($fp, true);
        restore_error_handler();
        if ($fp === false) {
            throw new Exception($errstr, $errno);
        }
        stream_set_timeout($fp, $timeout);
        return $fp;
    }
    /**
     * Server information.
     *
     * @var mixed
     */
    private $serverInfo;
    /**
     * Process information returned by the server after connection.
     *
     * @param string $connectionResponse INFO message.
     *
     * @return void
     */
    private function processServerInfo($connectionResponse)
    {
        $this->serverInfo = new ServerInfo($connectionResponse);
    }
    /**
     * Returns current connected server ID.
     *
     * @return string Server ID.
     */
    public function connectedServerID()
    {
        return $this->serverInfo->getServerID();
    }

    /**
     * Constructor.
     *
     * @param ConnectionOptions $options Connection options object.
     * @throws Exception
     */
    public function __construct(ConnectionOptions $options = null)
    {
        $this->pings         = 0;
        $this->pubs          = 0;
        $this->subscriptions = [];
        $this->options       = $options;
        if (version_compare(phpversion(), '7.0', '>') === true) {
            $this->randomGenerator = new Php71RandomGenerator();
        } else {
            $randomFactory         = new Factory();
            $this->randomGenerator = $randomFactory->getLowStrengthGenerator();
        }
        if ($options === null) {
            $this->options = new ConnectionOptions();
        }
    }
    /**
     * Sends data thought the stream.
     *
     * @param string $payload Message data.
     *
     * @return void
     * @throws \Exception Raises if fails sending data.
     */
    private function send($payload)
    {
//        $payload = json_encode($payload);
        $msg = $payload."\r\n";
        $len = strlen($msg);
        while (true) {
            if (($this->lastSentMessageTime !== 0) && ((time() - $this->lastSentMessageTime) > $this->timeout/2)) {
                $this->lastSentMessageTime = time();
                $this->reconnect();
            }
            $written = @fwrite($this->streamSocket, $msg);
            if ($written === false) {
                throw new \Exception('Error sending data (Error writing data to the connection)');
            }
            if ($written === 0) {
                throw new \Exception('Broken pipe or closed connection (reset by peer)');
            }
            $len = ($len - $written);
            if ($len > 0) {
                $msg = substr($msg, (0 - $len));
            } else {
                $this->lastSentMessageTime = time();
                break;
            }
        }
        if ($this->debug === true) {
            printf('>>>> %s', $msg);
        }
    }
    /**
     * Receives a message thought the stream.
     *
     * @param integer $len Number of bytes to receive.
     *
     * @return string
     * @throws \Exception
     */
    private function receive($len = 0)
    {
        stream_set_blocking($this->streamSocket, false);
        $line = false;
        while ($line == false) {
            $this->ping();
            if ($len > 0) {
                $chunkSize     = $this->chunkSize;
                $line          = null;
                $receivedBytes = 0;
                while ($receivedBytes < $len) {
                    $bytesLeft = ($len - $receivedBytes);
                    if ($bytesLeft < $this->chunkSize) {
                        $chunkSize = $bytesLeft;
                    }
                    $readChunk      = fread($this->streamSocket, $chunkSize);
                    $receivedBytes += strlen($readChunk);
                    $line          .= $readChunk;
                }
            } else {
                $line = fgets($this->streamSocket);
            }
            if ($line==false) {
                //задержка, чтобы не жрать CPU как бешеный, но при этом быть на линии, чтобы не отваливаться по таймауту.
                usleep(10000);
            }
        }
        stream_set_blocking($this->streamSocket, true);
        if ($this->debug === true) {
            printf("<<<< %s\r\n", $line);
        }
        return $line;
    }
    /**
     * Handles PING command.
     *
     * @return void
     * @throws \Exception
     */
    private function handlePING()
    {
        $this->send('PONG');
    }

    /**
     * Handles MSG command.
     *
     * @param string $line Message command from Nats.
     *
     * @param null $prioritySubjectToReceive
     * @param bool $getRaw
     * @return Message|void
     * @throws Exception If subscription not found.
     * @codeCoverageIgnore
     */
    private function handleMSG($line, $prioritySubjectToReceive = null, $getRaw = false)
    {
        $parts   = explode(' ', $line);
        $subject = null;
        $length  = trim($parts[3]);
        $sid     = $parts[2];
        if (count($parts) === 5) {
            $length  = trim($parts[4]);
            $subject = $parts[3];
        } else if (count($parts) === 4) {
            $length  = trim($parts[3]);
            $subject = $parts[1];
        }
        $payload = $this->receive($length);
        $msg     = new Message($subject, $payload, $sid, $this);
        if (isset($this->subscriptions[$sid]) === false) {
            throw new Exception('No subscriptions found for '.$sid);
        }
        if ($getRaw) {
            return $msg;
        }
        if ($prioritySubjectToReceive===null) {
            $this->callMessageHandler($msg);
        } else {
            if ($subject==$prioritySubjectToReceive) {
                $this->callMessageHandler($msg);
            } else {
                $this->addMessageToBackPack($msg);
            }
        }
    }

    /**
     * @param Message $message
     */
    public function addMessageToBackPack(Message $message) {
        array_push($this->messagesBackpack, $message);
    }

    /**
     * Simple call message handler. Usefull, if message is in the backpack.
     * @param Message $message
     * @throws Exception
     */
    private function callMessageHandler(Message $message) {
        $func = $this->subscriptions[$message->getSid()];
        if (is_callable($func) === true) {
            $func($message);
        } else {
            throw new Exception('problem with pipe message to callback. Sid: '.$message->getSid());
        }
    }

    /**
     * Connect to server.
     *
     * @param float $timeout Number of seconds until the connect() system call should timeout.
     *
     * @throws \Exception Exception raised if connection fails.
     * @return void
     */
    public function connect($timeout = null)
    {
        if ($timeout === null) {
            $timeout = intval(ini_get('default_socket_timeout'));
        }
        if ($timeout > 50) {
            $timeout = 50;
        }
        $this->timeout      = $timeout;
        $this->streamSocket = $this->getStream($this->options->getAddress(), $timeout);
        $this->setStreamTimeout($timeout);
        $msg = 'CONNECT '.$this->options;
        $this->send($msg);
        $connectResponse = $this->receive();
        if ($this->isErrorResponse($connectResponse) === true) {
            throw new Exception('Error response found: '.$connectResponse);
        } else {
            $this->processServerInfo($connectResponse);
        }
        $this->ping();
        $pingResponse = $this->receive();
        if ($this->isErrorResponse($pingResponse) === true) {
            throw new Exception('Error response found: '.$pingResponse);
        }
    }
    /**
     * Sends PING message.
     *
     * @return void
     * @throws \Exception
     */
    public function ping()
    {
        if ((time()-$this->lastPingTime)>$this->options->getPingMinInterval()) {
            $msg = 'PING';
            $this->send($msg);
            $this->pings += 1;
            $this->lastPingTime = time();
        }
    }
    /**
     * Request does a request and executes a callback with the response.
     *
     * @param string $subject Message topic.
     * @param string $payload Message data.
     * @param \Closure $callback Closure to be executed as callback.
     *
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function request($subject, $payload, \Closure $callback)
    {
        $inbox = uniqid('_INBOX.');
        $sid   = $this->subscribe(
            $inbox,
            $callback
        );
        $this->unsubscribe($sid, 1);
        $this->publish($subject, $payload, $inbox);
        $this->wait(1, $subject);
    }
    /**
     * Subscribes to an specific event given a subject.
     *
     * @param string $subject Message topic.
     * @param \Closure $callback Closure to be executed as callback.
     *
     * @return string
     * @throws \Exception
     */
    public function subscribe($subject, \Closure $callback)
    {
        $sid = $subject.$this->randomGenerator->generateString(16);
        $msg = 'SUB '.$subject.' '.$sid;
        $this->send($msg);
        $this->subscriptions[$sid] = $callback;
        return $sid;
    }
    /**
     * Subscribes to an specific event given a subject and a queue.
     *
     * @param string $subject Message topic.
     * @param string $queue Queue name.
     * @param \Closure $callback Closure to be executed as callback.
     *
     * @return string
     * @throws \Exception
     */
    public function queueSubscribe($subject, $queue, \Closure $callback)
    {
        $sid = $subject.'-'.$queue.$this->randomGenerator->generateString(16);
        $msg = 'SUB '.$subject.' '.$queue.' '.$sid;
        $this->send($msg);
        $this->subscriptions[$sid] = $callback;
        return $sid;
    }
    /**
     * Unsubscribe from a event given a subject.
     *
     * @param string $sid Subscription ID.
     * @param integer $quantity Quantity of messages.
     *
     * @return void
     * @throws \Exception
     */
    public function unsubscribe($sid, $quantity = null)
    {
        $msg = 'UNSUB '.$sid;
        if ($quantity !== null) {
            $msg = $msg.' '.$quantity;
        }
        $this->send($msg);
        if ($quantity === null) {
            unset($this->subscriptions[$sid]);
        }
    }
    /**
     * Publish publishes the data argument to the given subject.
     *
     * @param string $subject Message topic.
     * @param string $payload Message data.
     * @param string $inbox Message inbox.
     *
     * @return void
     *
     * @throws Exception If subscription not found.
     * @throws \Exception
     */
    public function publish($subject, $payload = null, $inbox = null)
    {
        $msg = 'PUB '.$subject;
        if ($inbox !== null) {
            $msg = $msg.' '.$inbox;
        }
        $msg = $msg.' '.strlen($payload);
        $this->send($msg."\r\n".$payload);
        $this->pubs += 1;
    }

    /**
     * Get one message from backpack (already received)
     * @return Message
     */
    private function getMessageFromBackPack(): Message
    {
        $key = array_key_first($this->messagesBackpack);
        $message = $this->messagesBackpack[$key];
        unset($this->messagesBackpack[$key]);
        return $message;
    }

    /**
     * Waits for messages.
     *
     * @param integer $quantity Number of messages to wait for.
     *
     * @param null $prioritySubjectToReceive
     * @param bool $getRaw dont send message to callback
     * @return Client|Message $connection Connection object
     * @throws Exception
     */
    public function wait($quantity = 0, $prioritySubjectToReceive=null, $getRaw = false)
    {
        $start_time = time();
        $count = 0;

        while (count($this->messagesBackpack)>0) {
            if ($quantity!==0 && $count>=$quantity) break;
            $message = $this->getMessageFromBackPack();
            if ($getRaw) {
                return $this->handleMSG($message, $prioritySubjectToReceive, $getRaw);
            } else {
                $this->handleMSG($message, $prioritySubjectToReceive, $getRaw);
            }
            $count++;
        }

        $info  = stream_get_meta_data($this->streamSocket);
        while (is_resource($this->streamSocket) && !feof($this->streamSocket) && empty($info['timed_out'])) {
            if ((time() - $start_time) > $this->waitingMessageTimeout) {
                throw new Exception("Timeout waiting message");
            }
            $line = $this->receive();
            if ($line === false) {
                return null;
            }
            if (strpos($line, 'PING') === 0) {
                $this->handlePING();
            }
            if (strpos($line, 'MSG') === 0) {
                $count++;
                if ($getRaw) {
                    return $this->handleMSG($message, $prioritySubjectToReceive, $getRaw);
                } else {
                    $this->handleMSG($message, $prioritySubjectToReceive, $getRaw);
                }
                if (($quantity !== 0) && ($count >= $quantity)) {
                    return $this;
                }
            }
            $info = stream_get_meta_data($this->streamSocket);
        }
        $this->close();
        return $this;
    }
    /**
     * Reconnects to the server.
     *
     * @return void
     * @throws \Exception
     */
    public function reconnect()
    {
        $this->reconnects += 1;
        $this->close();
        $this->connect($this->timeout);
    }
    /**
     * Close will close the connection to the server.
     *
     * @return void
     */
    public function close()
    {
        if ($this->streamSocket === null) {
            return;
        }
        fclose($this->streamSocket);
        $this->streamSocket = null;
    }
    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
