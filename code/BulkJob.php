<?php

namespace SilverStripe\Intercom;

use Guzzle\Service\Client;

class BulkJob
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $id;

    /**
     * @param Client $client
     * @param int $id
     */
    public function __construct(Client $client, $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->client->getJob(["id" => $this->id]);
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->client->getJobErrors(["id" => $this->id]);
    }
}
