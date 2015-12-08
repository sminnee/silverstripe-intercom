<?php

namespace Sminnee\SilverStripeIntercom\Tests;

use Sminnee\SilverStripeIntercom\RequestFilter;
use Sminnee\SilverStripeIntercom\IntercomScriptTags;

use SapphireTest;
use SS_HTTPRequest;
use SS_HTTPResponse;
use DataModel;
use Session;

class RequestFilterTest extends SapphireTest {

	/**
	 * Keep track of some global state to restore in tearDown
	 */
	protected $saved;

	function setUp() {
		$this->saved = array(
			'script_tags_enabled' => IntercomScriptTags::config()->enabled,
		);
	}

	function tearDown() {
		IntercomScriptTags::config()->enabled = $this->saved['script_tags_enabled'];
	}

	/**
	 * @dataProvider sampleResponses
	 */
	function testScriptInsertion($response, $match) {
		// Check that script has been added before the body
		$this->assertEquals(
			$match,
			preg_match(
				'/<script>.*window.intercomSettings *= *.*<\/body>/is',
				$this->checkFilterForResponse($response)->getBody()
			)
		);
	}

	function sampleResponses() {
		$test = array();

		// Regular responses
		$test[] = array(new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>"), 1);

		// Fragment response without a </body> doesn't have code added
		$test[] = array(new SS_HTTPResponse("<p>fragment doesn't have script added</p>"), 0);

		// Plaintext response doesn't have code added
		$response = new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");
		$response->addHeader("Content-Type", "text/plain");
		$test[] = array($response, 0);

		return $test;
	}

	/**
	 * Test that no script is addded if IntercomScriptTags is disabled
	 */
	function testScriptTagsDisabling() {
		IntercomScriptTags::config()->enabled = false;

		$response = new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");

		// Check that script has been added before the body
		$this->assertEquals(
			0,
			preg_match(
				'/<script>.*window.intercomSettings *= *.*<\/body>/is',
				$this->checkFilterForResponse($response)->getBody()
			)
		);
	}

	/**
	 * Set up test scaffold to check the RequestFilter's effect on a response
	 */
	function checkFilterForResponse($response) {
		// Test stub
		$request = new SS_HTTPRequest("GET", "/");
		$model = new DataModel();
		$session = new Session(array());

		// Execute the filter
		$filter = new RequestFilter();
		$filter->preRequest($request, $session, $model);
		$filter->postRequest($request, $response, $model);

		return $response;
	}



}
