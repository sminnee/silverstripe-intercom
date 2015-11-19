<?php

namespace Sminnee\SilverStripeIntercom;

use SS_HTTPRequest;
use SS_HTTPResponse;
use Session;
use DataModel;

class RequestFilter implements \RequestFilter
{
	/**
	 * Does nothing
	 */
	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {

	}

	/**
	 * Adds Intercom script tags just before the body
	 */
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {		
		$mime = $response->getHeader('Content-Type');
		if(!$mime || strpos($mime, 'text/html') !== false) {
			$intercomScriptTags = (new IntercomScriptTags())->forTemplate();

			if($intercomScriptTags) {
				$content = $response->getBody();
				$content = preg_replace("/(<\/body[^>]*>)/i", $intercomScriptTags . "\\1", $content);
				$response->setBody($content);
			}			
		}
	}
}
