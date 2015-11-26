<?php

namespace Sminnee\SilverStripeIntercom;

use LogicException;
use Intercom\IntercomBasicAuthClient;
use Member;

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
		return $this->client->getJob(['id' => $this->id]);
	}

	function getErrors(){
		return $this->client->getJobErrors(['id' => $this->id]);
	}
}