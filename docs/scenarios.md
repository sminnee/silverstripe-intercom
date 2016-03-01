# Scenarios

The `ConnectLeadsAndConversations` task (mentioned in the [tasks](tasks.md) section) is a majestic animal! It is responsible for tagging and assigning Intercom leads and conversations, thanks to the following interface:

```php
namespace SilverStripe\Intercom\Task;

interface LeadAndConversationConnector
{
    /**
     * @return array
     */
    public function getTags();

    /**
     * @return string
     */
    public function getTeamIdentifier();

    /**
     * @return bool
     */
    public function shouldConnect(array $lead, array $notes);

    /**
     * @return string
     */
    public function getMessage(array $lead, array $notes);
}
```

You should create an implementation of this every time you want to tag or assign a _new kind_ of Intercom lead. For example, you may want to tag all leads from a certain email domain:

```php
public function shouldConnect(array $lead, array $notes)
{
    return stristr($lead["email"], "silverstripe.com");
}
```

Maybe you would like to tag any leads with a URL in any of their notes:

```php
public function shouldConnect(array $lead, array $notes)
{
    foreach ($notes as $note) {
        preg_match("#URL: (\S+)#", $note["body"], $matches);

        if (count($matches) > 0) {
            return true;
        }
    }

    return false;
}
```

The string you return, from `getMessage` will be the content of the Intercom conversation that gets created. You can choose any number of tags to apply to an Intercom lead:

```php
public function getTags()
{
    return ["Fresh", "Prince"];
}
```

If these tags do not exist in Intercom, they will be created automatically, and applied to the Intercom lead. You can also assign an admin or team to the Intercom conversation:

```php
public function getTeamIdentifier()
{
    return SilverStripe\Intercom\Model\Team::get()
        ->filter("name", "Bel Air")
        ->first();
}
```

The `UpdateTeams` task should have run by this point.

You need to tell the module which scenarios to run, with the following config:

```yaml
SilverStripe\Intercom\Task\ConnectLeadsAndConversations:
  connectors:
    - FreshPrinceConnector
  assignment_admin_id: 123456
```

You also need to provide an IntercomID for the admin or team that has permission to assign tags and teams.
