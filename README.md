# autosuspend
Autosuspend plugin for Oxwall. Automatically suspends users who have been flagged a certain number of times.


Admin can adjust:

- Number of flags required before suspending
- What type of flagged items to look for
- Cron frequency (how often to run checks)
- Suspension-reason shown to suspended users
- Whether Moderators should be suspended

Can be adjusted to check or ignore flags on the following items:

- User Profiles
- Status Updates
- Forum Topics
- Forum Replies
- Groups
- Photos
- Videos
- Comments

Resource-respectful:
The plug-in will only run when it detects that new flags have been added since the last check.

Smart-checks:
The plug-in is smart enough to detect abusive behavior. If a single user flags someone else multiple times, the plug-in will only count 1 flag. This way, users can't get someone suspended by simply flagging all their content.
