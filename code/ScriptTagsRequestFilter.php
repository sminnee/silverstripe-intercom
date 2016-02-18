<?php

namespace SilverStripe\Addon\Intercom;

use RequestFilter;
use SS_HTTPRequest;
use SS_HTTPResponse;
use Session;
use DataModel;
use ViewableData;

/**
 * Add HTML content before the </body> of a full HTML page.
 */
class ScriptTagsRequestFilter implements RequestFilter
{
    /**
     * @var ViewableData
     */
    private $tagProvider;

    /**
     * @inheritdoc
     *
     * @param SS_HTTPRequest $request
     * @param Session $session
     * @param DataModel $model
     *
     * @return bool
     */
    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
        return true;
    }

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
     * @inheritdoc
     *
     * @param SS_HTTPRequest $request
     * @param SS_HTTPResponse $response
     * @param DataModel $model
     *
     * @return bool
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
        $mime = $response->getHeader("Content-Type");

        if (!$mime || strpos($mime, "text/html") !== false) {
            $tags = $this->tagProvider->forTemplate();

            if ($tags) {
                $content = $response->getBody();
                $content = preg_replace("#(</body[^>]*>)#i", $tags . "\\1", $content);
                $response->setBody($content);
            }
        }

        return true;
    }
}
