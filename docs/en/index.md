# GPG Mailer developer documentation

## What is GPG/PGP?

 - [OpenPGP, PGP and GPG definitions](http://blog.goanywheremft.com/2013/07/18/openpgp-pgp-gpg-difference/)
 - [How PGP works](http://www.pgpi.org/doc/pgpintro/)
 - [GPG command line manual](https://www.gnupg.org/gph/en/manual/book1.html)
 - [GPG cheat sheet](http://irtfweb.ifa.hawaii.edu/~lockhart/gpg/gpg-cs.html)

## Using the GPG Mailer

### Configuration

To configure the module you need to [generate keyrings](managing-gpg-keys.md) including:

 - the public key of the recipient of the email that can be used to encrypt the email
 - optionally the private key and public key of the sender which can be used to sign the email

Usually the files are:
 - pubring.gpg
 - secring.gpg
 - trustdb.gpg

Configuration options for the Crypt_GPG class can be set through the `GPGMailer::options` config. For a full list of
config options see `Crypt_GPGAbstract::__construct()`.

#### Environment settings

These should be defined in your envrionment, or `.env` file above your project's root; Don't store passphrases in Git!

| Constant | Example | Explanation |
|----------|---------|-------------|
| `GPGMAILER_HOMEDIR` | `'/path/to/keyring/'` | Absolute path of the folder containing GPG keyring(s) |
| `GPGMAILER_ENCRYPT_KEY` | `'recipient@example.com'` | ID (usually email address) of the recipient's key |
| `GPGMAILER_SIGN_KEY` | `'sender@example.com'` | ID (usually email address) of the sender's key _(optional)_ |
| `GPGMAILER_SIGN_KEY_PASSPHRASE` | `'Y!M7oNG^w3x6dxUpu0u^'` | Passphrase for the sender's private key _(optional)_ |

### Sending encrypted email

Once you have generated your GPG keys and correctly configured SilverStripe CMS you can use `GPGMailer` class to encrypt
and sign emails.

File attachments are also encrypted. During testing the size of the encrypted attachment was about 30% larger than the
original.

#### Available options of Crypt_GPG 1.6.3 are:

- `string  homedir` - the directory where the GPG keyring files are stored. If not specified, Crypt_GPG uses the default of `~/.gnupg`.
- `string  publicKeyring`  - the file path of the public keyring. Use this if the public keyring is not in the homedir, or if the keyring is in a directory not writable by the process invoking GPG (like Apache). Then you can specify the path to the keyring with this option (/foo/bar/pubring.gpg), and specify a writable directory (like /tmp) using the <i>homedir</i> option.
- `string  privateKeyring` - the file path of the private keyring. Use this if the private keyring is not in the homedir, or if the keyring is in a directory not writable by the process invoking GPG (like Apache). Then you can specify the path to the keyring with this option (/foo/bar/secring.gpg), and specify a writable directory (like /tmp) using the <i>homedir</i> option.
- `string  trustDb` - the file path of the web-of-trust database. Use this if the trust database is not in the homedir, or if the database is in a directory not writable by the process invoking GPG (like Apache). Then you can specify the path to the trust database with this option (/foo/bar/trustdb.gpg), and specify a writable directory (like /tmp) using the <i>homedir</i> option.
- `string  binary`  - the location of the GPG binary. If not specified, the driver attempts to auto-detect the GPG binary location using a list of known default locations for the current operating system. The option `gpgBinary` is a deprecated alias for this option.
- `string  agent`   - the location of the GnuPG agent binary. The gpg-agent is only used for GnuPG 2.x. If not specified, the engine attempts to auto-detect the gpg-agent binary location using a list of know default locations for the current operating system.
- `string|false gpgconf`   - the location of the GnuPG conf binary. The gpgconf is only used for GnuPG >= 2.1. If not specified, the engine attempts to auto-detect the location using a list of know default locations. When set to FALSE `gpgconf --kill` will not be executed via destructor.
- `string digest-algo`     - Sets the message digest algorithm.
- `string cipher-algo`     - Sets the symmetric cipher.
- `boolean strict`  - In strict mode clock problems on subkeys and signatures are not ignored (--ignore-time-conflict and --ignore-valid-from options)
- `mixed debug`     - whether or not to use debug mode. When debug mode is on, all communication to and from the GPG subprocess is logged. This can be useful to diagnose errors when using Crypt_GPG.

### Implementation example

```php
// Create email with plain content
$content = $this->customise($output)->renderWith('SomeTemplate')->forTemplate();
$email = new Email(
	'sender@example.com',
	'recipient@example.com',
	$subject,
	$content
);

// Use encrypted mailer
$email->set_mailer(new GPGMailer());

// HTML emails cannot be encrypted so just send plain
$result = $email->sendPlain();
```
