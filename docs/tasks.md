# Scheduled Tasks

The module has a few automated tasks, which require the `silverstripe/cron-task` module to run. You can install and configure it, by following the instructions provided with that module.

Once it is correctly installed and configured, this module will run the automated tasks at the following times:

- Updating Intercom tags → every minute
- Updating Intercom teams → every minute
- Creating, tagging and assigning Intercom conversations to teams → every hour

You can change these frequencies with the following config settings:

```yaml
SilverStripe\Intercom\CronTask\UpdateTags:
    schedule: */5 * * * *
SilverStripe\Intercom\CronTask\UpdateTeams:
    schedule: false
SilverStripe\Intercom\CronTask\ConnectLeadsAndConversations:
    schedule: * * */1 * *
 ```

 These schedule formats (with the exception of falsey values) follow the same rules as regular CRON tasks. You can also run the corresponding build tasks manually:

```sh
$ framework/sake dev/tasks/SilverStripe-Intercom-Task-UpdateTags
$ framework/sake dev/tasks/SilverStripe-Intercom-Task-UpdateTeams
$ framework/sake dev/tasks/SilverStripe-Intercom-Task-ConnectLeadsAndConversations
```

The `ConnectLeadsAndConversations` task does a few things:

- create new Intercom conversations for unprocessed Intercom leads
- tag unprocessed Intercom leads with one or more Intercom tags
- assign the new Intercom conversations to an Intercom admin or team

The specifics of how this module knows what to tag and assign can be found in the [scenarios](scenarios.md) section.
