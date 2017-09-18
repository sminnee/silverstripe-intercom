<?php

namespace Sminnee\SilverStripeIntercom;

/**
 * Reference to a bulk job result
 */
class IntercomBulkJob
{

    protected $client;
    protected $id;

    public function __construct($client, $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInfo()
    {
        return $this->client->get('jobs/' . $this->id, null);
    }

    public function getErrors()
    {
        return $this->client->get('jobs/' . $this->id . '/error', null);
    }
}
