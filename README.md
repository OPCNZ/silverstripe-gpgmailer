# GPG Encryption for SilverStripe Mailer

The GPG Mailer module allows emails being sent from SilverStripe CMS to be encrypted and/or signed using GPG public and private keys. It improves the privacy of email communications sent over standard email protocols and allows recipients of signed emails to ensure the message has been sent from a known sender.

This requires the transfer of public keys between the sender and recipient, it also requires GPG software to be installed on the server SilverStripe CMS is running on. A current module limitation is that only plain text emails can be encrypted (not HTML emails).

## Requirements
-  SilverStripe Framework (silverstripe/framework ~3.1)
-  PEAR Crypt GPG (pear/crypt_gpg 3f24905839720b2f433f241289b3d03210b2a74e)

## Install

```
composer require opcnz/gpg-mailer ~1.0
```

## Documentation

See the [developer documentation](docs/en/index.md)

## Example configuration

__Note:__ Only use ```relative_homedir``` for test environments as it refers to a folder structure relative to the webroot and is therefore publically exposed, see the [configuration docs](docs/en/configuration.md) for more info.

```yaml
---
Only:
  environment: 'dev'
---
GPGMailer:
  options:
      relative_homedir: 'assets/'
      debug: true
---
Only:
  environment: 'test'
---
GPGMailer:
  options:
    - homedir: '/path/to/keyring/'
      debug: false
---
Only:
  environment: 'live'
---
GPGMailer:
  options:
    - homedir: '/path/to/keyring/'
      debug: false
```

## Maintainers
- Frank Mullenger <frank@silverstripe.com>

## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over existing issues to ensure yours is unique. 

If the issue does look like a new bug:

- Create a new issue
- Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots and screencasts can help here.
- Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.


