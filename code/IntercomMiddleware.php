<?php

namespace SilverStripe\Intercom;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\View\ViewableData;

/**
 * Add HTML content before the </body> of a full HTML page.
 * Used to include IntercomScriptTags into a page
 */
class IntercomMiddleware implements HTTPMiddleware
{
    /**
     * @var ViewableData
     */
    protected $tagProvider;

    /**
     * Provide a ViewableData object that will render the tags to include.
     *
     * @param ViewableData $tagProvider
     */
    public function setTagProvider(ViewableData $tagProvider)
    {
        $this->tagProvider = $tagProvider;
    }

    /**
     * Adds Intercom script tags just before the body
     *
     * @param  HTTPRequest $request
     * @param  callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var HTTPResponse $response */
        $response = $delegate($request);

        $mime = $response->getHeader('Content-Type');
        if (!$mime || strpos($mime, 'text/html') !== false) {
            $tags = $this->tagProvider->forTemplate();

            if ($tags) {
                $content = $response->getBody();
                $content = preg_replace("/(<\/body[^>]*>)/i", $tags . "\\1", $content);
                $response->setBody($content);
            }
        }

        return $response;
    }
}
