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

These should be defined in your `_ss_environment.php` file, above your webroot. Don't store passphrases in Git!

| Constant | Example | Explanation |
|----------|---------|-------------|
| `GPGMAILER_HOMEDIR` | `'/path/to/keyring/'` | Absolute path of the folder containing GPG keyring(s) |
| `GPGMAILER_ENCRYPT_KEY` | `'recipient@example.com'` | ID (usually email address) of the recipient's key |
| `GPGMAILER_SIGN_KEY` | `'sender@example.com'` | ID (usually email address) of the sender's key _(optional)_ |
| `GPGMAILER_SIGN_KEY_PASSPHRASE` | `'senderPa55w0rd'` | Passphrase for the sender's private key _(optional)_ |

### Sending encrypted email

Once you have generated your GPG keys and correctly configured SilverStripe CMS you can use `GPGMailer` class to encrypt
and sign emails.

Only plaintext emails can be encrypted, trying to send HTML emails using `Email::send()` and in turn
`GPGMailer::sendHTML()` triggers a warning and sends the email using `GPGMailer::sendPlain()`.

File attachments are also encrypted. During testing the size of the encrypted attachment was about 30% larger than the
original.

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


