fake-mechanical-turk
====================

Acts like an Amazon Mechanical Turk endpoint but does some other neat stuff on top of that.

Requirements
------------
* PHP 5.4 compiled with SQLite support
* Apache 2.2 (recommended)

Installation
------------

```sh
git clone https://github.com/jonchang/fake-mechanical-turk
cd fake-mechanical-turk
make help
make setup-rsync
vim rsync_target
make deploy
```



Use cases
---------

A Requester has created an ExternalHIT written in purely JavaScript and HTML, which only handles one "task" at a time. Amazon Mechanical Turk passes parameters to this static page, which parses those parameters to determine which HIT to display to the Worker. Once the Worker has completed their task, the page POSTs the results back to Amazon for review by the Requester.

A Requester should be able to:

*   batch requests, such that workers must complete 5 HITs in one session rather than 1 at a time.
*   use external testing, so that Requester using both MTurk and non-MTurk crowdsourcing resources can get a vaguely similar experience with either.

In both cases, the only change required for the static HTML ExternalHIT should be switching the MTurk endpoint from Amazon's servers to this script, just like using the MTurk sandbox vs production servers.

Workflow
--------

1.  Set up an ExternalHIT that points to this script.
2.  Recruit MTurk Worker.
3.  Script selects appropriate task, redirects to static page with parameters filled.
4.  Worker completes task, POSTs to this script.
5.  Script checkpoints result, selects new task.
6.  Repeat 4 & 5 until the batch limit is reached.
7.  Optionally allow Worker to continue working for bonus pay.
8.  Script collates results, Worker clicks "submit" button to submit the completed HIT.
9.  Analyze data, pay Workers, etc.

Non-MTurk workflow should be the same as above, except in Steps 1 and 2 the Worker would be asked to input some other piece of identifying information, such as an email address or randomly generated hash.

Incomplete HITs
---------------

If the Worker stops working on a task but then comes back later to complete it, the script will read the checkpoint file and resume where they left off. If the HIT expires before the Worker can finish it the Requester can always pay a prorated bonus based on the amount of work completed.
