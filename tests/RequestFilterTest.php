<?php

namespace Sminnee\SilverStripeIntercom\Tests;

use Sminnee\SilverStripeIntercom\RequestFilter;

use SapphireTest;
use SS_HTTPRequest;
use SS_HTTPResponse;
use DataModel;
use Session;
use DBField;

class RequestFilterTest extends SapphireTest {

	/**
	 * @dataProvider sampleResponses
	 */
	function testScriptInsertion($response, $match) {

		// Simulating an enabled script tag
		$tag = DBField::create_field('HTMLText', '<script>test;</script>');

		// Check that script has been added before the body
		if($match) {
			$this->assertRegExp(
				'/<script>test;<\/script><\/body>/is',
				$this->checkFilterForResponse($response, $tag)->getBody()
			);
		} else {
			$this->assertNotRegExp(
				'/<script>test;<\/script><\/body>/is',
				$this->checkFilterForResponse($response, $tag)->getBody()
			);
		}
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
		$response = new SS_HTTPResponse("<html><head></head><body><p>regular response has script added</p></body></html>");

		// Empty response, simulating a disabled script tag
		$tag = DBField::create_field('HTMLText', '');

		// Check that script has been added before the body
		$this->assertRegExp(
			'/<\/p><\/body>/i',
			$this->checkFilterForResponse($response, $tag)->getBody()
		);
	}

	/**
	 * Set up test scaffold to check the RequestFilter's effect on a response
	 */
	function checkFilterForResponse($response, $tag) {
		// Test stub
		$request = new SS_HTTPRequest("GET", "/");
		$model = new DataModel();
		$session = new Session(array());

		// Execute the filter
		$filter = new RequestFilter();
		$filter->setTagProvider($tag);

		$filter->preRequest($request, $session, $model);
		$filter->postRequest($request, $response, $model);

		return $response;
	}



}
