<?php
namespace Vladitot\Nats\Client;

class Message
{
    /**
     * Message Subject.
     *
     * @var string
     */
    private $subject;
    /**
     * Message Body.
     *
     * @var string
     */
    public $body;
    /**
     * Message Ssid.
     *
     * @var string
     */
    private $sid;
    /**
     * Message related connection.
     *
     * @var Client
     */
    private $client;
    /**
     * Нужно ли отвечать на это сообщение
     *
     * @var bool
     */
    private $needResponse;
    /**
     * Original channel name
     * @var string
     *
     */
    private $originalSubject;
    /**
     * Message constructor.
     *
     * @param string     $subject Message subject.
     * @param string     $body    Message body.
     * @param string     $sid     Message Sid.
     * @param Client $conn    Message Connection.
     */
    public function __construct($subject, $body, $sid, Client $conn)
    {
        $this->setSubject($subject);
        $this->setBody($body);
        $this->setSid($sid);
        $this->setClient($conn);
        $this->needResponse(
            (strripos($this->subject, '_INBOX')!==false)
        );
    }
    /**
     * Set subject.
     *
     * @param string $subject Subject.
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }
    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }
    /**
     * Set body.
     *
     * @param string $body Body.
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * Set Ssid.
     *
     * @param string $sid Ssid.
     *
     * @return $this
     */
    public function setSid($sid)
    {
        $this->sid = $sid;
        return $this;
    }
    /**
     * Get Ssid.
     *
     * @return string
     */
    public function getSid()
    {
        return $this->sid;
    }
    /**
     * String representation of a message.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getBody();
    }
    /**
     * Set Conn.
     *
     * @param Client $client Connection.
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }
    /**
     * Get Conn.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Allows you reply the message with a specific body.
     *
     * @param string $body Body to be set.
     *
     * @return void
     * @throws \Exception
     */
    public function reply($body)
    {
        if ($this->needResponse) {
            $this->client->publish(
                $this->subject,
                $body
            );
        }
    }
    /**
     * @param bool $needResponse
     */
    private function needResponse($needResponse)
    {
        $this->needResponse = $needResponse;
    }
    /**
     * @return mixed
     */
    public function getNeedResponse()
    {
        return $this->needResponse;
    }
    /**
     * @return string
     */
    public function getOriginalSubject()
    {
        return $this->originalSubject;
    }
    /**
     * @param string $originalSubject
     */
    public function setOriginalSubject($originalSubject)
    {
        $this->originalSubject = $originalSubject;
    }
}