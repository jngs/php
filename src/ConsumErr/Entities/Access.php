<?php

namespace ConsumErr\Entities;


use ConsumErr\ConsumErr;

class Access
{

    /**
     * @var integer
     */
    private $datetime;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var float
     */
    private $time;

    /**
     * @var integer
     */
    private $memory;

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var bool
     */
    private $backgroundJob = FALSE;

    /**
     * @var string
     */
    private $versions = array(
        ConsumErr::EXTENSION_NAME => ConsumErr::VERSION_CODE
    );

    /**
     * @var integer
     */
    private $type = 0;

    /**
     * @var \ConsumErr\Entities\Event[]
     */
    private $events = array();

    /**
     * @var \ConsumErr\Entities\Error[]
     */
    private $errors = array();

    /**
     * @var \ConsumErr\Entities\Part[]
     */
    private $parts = array();

    /** @var string */
    private $serverName;

    /** @var string */
    private $remoteAddr;

    /** @var string */
    private $userAgent;


    /**
     *
     */
    function __construct()
    {
        $this->datetime = time();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        $data = get_object_vars($this);
        foreach (array('events', 'errors', 'parts') as $var) {
            foreach ($data[$var] as &$object) {
                $object = $object->__toArray();
            }
        }

        return base64_encode(json_encode($data));
    }


    /**
     * @return integer
     */
    public function getDatetime()
    {
        return $this->datetime;
    }


    /**
     * @param string $name
     * @return Access
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param string $url
     * @return Access
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * @param bool $backgroundJob
     * @return Access
     */
    public function setBackgroundJob($backgroundJob)
    {
        $this->backgroundJob = $backgroundJob;

        return $this;
    }


    /**
     * @return bool
     */
    public function getBackgroundJob()
    {
        return $this->backgroundJob;
    }


    /**
     * @param float $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }


    /**
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }


    /**
     * @param integer $memory
     * @return Access
     */
    public function setMemory($memory)
    {
        $this->memory = $memory;

        return $this;
    }


    /**
     * @return integer
     */
    public function getMemory()
    {
        return $this->memory;
    }

    public function addExtensionVersion($name, $versionCode)
    {
        $this->versions[$name] = $versionCode;

        return $this;
    }

    /**
     * @param \ConsumErr\Entities\Error $error
     * @return Access
     */
    public function addError(Error $error)
    {
        $this->errors[] = $error;

        return $this;
    }


    /**
     * @return \ConsumErr\Entities\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * @param \ConsumErr\Entities\Event $event
     * @return Access
     */
    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }


    /**
     * @return \ConsumErr\Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }


    /**
     * @param \ConsumErr\Entities\Part[] $part
     * @return Access
     */
    public function addPart(Part $part)
    {
        $this->parts[] = $part;

        return $this;
    }


    /**
     * @return \ConsumErr\Entities\Part[]
     */
    public function getParts()
    {
        return $this->parts;
    }


    /**
     * @param integer $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * @param string $host
     */
    public function setServerName($host)
    {
        $this->serverName = $host;
    }

    /**
     * @return string
     */
    public function getRemoteAddr()
    {
        return $this->remoteAddr;
    }

    /**
     * @param string $remoteAddr
     */
    public function setRemoteAddr($remoteAddr)
    {
        $this->remoteAddr = $remoteAddr;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }


}
