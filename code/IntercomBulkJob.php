<?php

namespace Sminnee\SilverStripeIntercom;

/**
 * Reference to a bulk job result
 */
class IntercomBulkJob
{

	protected $client;
	protected $id;

	function __construct($client, $id) {
		$this->client = $client;
		$this->id = $id;
	}

	function getId() {
		return $this->id;
	}

	function getInfo(){
		return $this->client->get('jobs/' . $this->id, null);
	}

	function getErrors(){
		return $this->client->get('jobs/' . $this->id . '/error', null);
	}
}
