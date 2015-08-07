# GPG Mailer developer documentation

## General GPG infromation
These useful links will help you understand GPG (compatible with the OpenPGP standard).

- [OpenPGP, PGP and GPG definitions](http://blog.goanywheremft.com/2013/07/18/openpgp-pgp-gpg-difference/)
- [How PGP works](http://www.pgpi.org/doc/pgpintro/)
- [GPG command line manual](https://www.gnupg.org/gph/en/manual/book1.html)
- [GPG cheat sheet](http://irtfweb.ifa.hawaii.edu/~lockhart/gpg/gpg-cs.html)

## Using the GPG Mailer

### GPG keys
See [Managing GPG keyrings](managing-gpg-keyrings.md)

### Configuration
See [Configuration](configuration.md)

### Sending encrypted email
One you have gerenated your GPG keys and correctly configured SilverStripe CMS you can use `GPGMailer` class to encrypt and sign emails.

Only plaintext emails can be encrypted, trying to send HTML emails using `Email::send()` and in turn `GPGMailer::sendHTML()` triggers a warning and sends the email using `GPGMailer::sendPlain()`.

File attachments are also encrypted. During testing the size of the encrypted attachment was about 30% 
larger than the original.

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

// Set up encryption mailer
$mailer = new GPGMailer(
	'frank@swipestripe.com',
	'sender@example.com', 
	'senderPa55w0rd'
);
$email->set_mailer($mailer);

// HTML emails cannot be encrypted so just send plain
$result = $email->sendPlain();
```


