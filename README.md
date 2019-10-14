# GPG Encryption for SilverStripe Mailer

The GPG Mailer module allows emails being sent from SilverStripe CMS to be encrypted and/or signed using GPG public and
private keys. It improves the privacy of email communications sent over standard email protocols and allows recipients
of signed emails to ensure the message has been sent from a known sender.

## Requirements
 - SilverStripe Framework (silverstripe/framework ^4)
 - PEAR Crypt GPG (pear/crypt_gpg)
 - GnuPG binary installed

## Known issues
 - Only plain text emails can be encrypted

## Install

```
composer require opcnz/silverstripe-gpgmailer
```

## Documentation

See the [developer documentation](docs/en/index.md)

## Maintainers

 - Frank Mullenger <frank@silverstripe.com>
 - Ed Linklater <ed@edgar.industries>

## Bugtracker

Bugs are tracked in the issues section of this repository. Before submitting an issue please read over existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.
