<?php

namespace SilverStripe\Intercom\Task;

use SilverStripe\Dev\BuildTask;
use Guzzle\Service\Resource\Model;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Intercom\Intercom;
use SilverStripe\Intercom\Model\Tag;
use SilverStripe\Control\HTTPRequest;

class UpdateTags extends BuildTask
{
    /**
     * @inheritdoc
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $client = Injector::inst()->get(Intercom::class);

        /** @var Model $response */
        $response = $client->getClient()->tags->getTags();
        $tags = $response->tags;
        $ids = [];

        foreach ($tags as $tag) {
            $ids[] = $tag->id;

            $existing = Tag::get()->filter("IntercomID", $tag->id)->first();

            if (!$existing) {
                $this->line("- creating record for " . $tag->name);

                $existing = Tag::create();
                $existing->Type = $tag->type;
                $existing->IntercomID = $tag->id;
            } else {
                $this->line("- updating record for " . $tag->name);
            }

            $existing->Name = $tag->name;
            $existing->write();
        }

        $tags = Tag::get();

        foreach ($tags as $tag) {
            if (!in_array($tag->IntercomID, $ids)) {
                $this->line("- updating record for " . $tag->Name);
                $tag->delete();
            }
        }
    }

    /**
     * @param string $message
     */
    private function line($message)
    {
        print $message . "\n";
        flush();
    }
}
