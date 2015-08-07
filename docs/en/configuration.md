# Configuration
To configure the module you need to [generate keyrings](managing-gpg-keys.md) including:

- the public key of the recipient of the email that can be used to encrypt the email
- optionally the private key and public key of the sender which can be used to sign the email

Usually the files are:
- pubring.gpg
- secring.gpg
- trustdb.gpg

Configuration options for the Crypt_GPG class can be set through the `GPGMailer::options` settings 
in the config.yml file.

For a full list of config options see `Crypt_GPGAbstract::__construct()` and `GPGMailer::setOptions()`.

You must set a directory that contains your GPG keys. This can be either absolute (`homedir`) or relative to your project (`relative_homedir`). You can set different key directories depending on  SilverStripe environment type in your `mysite/_config/config.yml` file (see example below).

__Note:__ Only use ```relative_homedir``` for test environments as it refers to a folder structure relative to the webroot and is therefore publically exposed.

## Example configuration

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