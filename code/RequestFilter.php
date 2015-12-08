<?php

namespace Sminnee\SilverStripeIntercom;

use SS_HTTPRequest;
use SS_HTTPResponse;
use Session;
use DataModel;
use ViewableData;

/**
 * Add HTML content before the </body> of a full HTML page.
 * Used to include IntercomScriptTags into a page
 */
class RequestFilter implements \RequestFilter
{
	/**
	 * Does nothing
	 */
	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {

	}

	/**
	 * Provide a ViewableData object that will render the tags to include.
	 */
	public function setTagProvider(ViewableData $tagProvider) {
		$this->tagProvider = $tagProvider;
	}

	/**
	 * Adds Intercom script tags just before the body
	 */
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {
		$mime = $response->getHeader('Content-Type');
		if(!$mime || strpos($mime, 'text/html') !== false) {
			$tags = $this->tagProvider->forTemplate();

			if($tags) {
				$content = $response->getBody();
				$content = preg_replace("/(<\/body[^>]*>)/i", $tags . "\\1", $content);
				$response->setBody($content);
			}
		}
	}
}
