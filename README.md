# GPG Encryption for Email

## Requirements (see composer.json)
* Silverstripe Framework 3.1
* PEAR Crypt GPG 

## Install
composer require frankmullenger/gpg-mailer dev-master

## Configuration
Requires keyring files which include 
- the public key of the recipient of the email which can be used to encrypt the email
- optionally the private key and public key of the sender which can be used to sign the email

Also optionally need to trust the public key for encrypting. 

Usually the files are:
- pubring.gpg
- secring.gpg
- trustdb.gpg

Options for Crypt_GPG can be added to the YAML file such as the homedir where keyrings are stored, 
see Crypt_GPGAbstract::__construct() and GPGMailer::setOptions().
```
---
Only:
  environment: 'dev'
---
GPGMailer:
  options:
    - homedir: '/Users/someone/Sites/path/'
      relative_homedir: 'assets/'
      debug: true
---
Only:
  environment: 'test'
---
GPGMailer:
  options:
    - homedir: '/Users/someone/Sites/path/'
      relative_homedir: 'assets/'
      debug: true
---
Only:
  environment: 'live'
---
GPGMailer:
  options:
    - homedir: '/Users/someone/Sites/path/'
      debug: false
```

## Documentation
Useful links for uderstanding PGP and creating keys and keyrings:
- http://www.pgpi.org/doc/pgpintro/
- https://www.gnupg.org/gph/en/manual/book1.html
- http://irtfweb.ifa.hawaii.edu/~lockhart/gpg/gpg-cs.html

### Sending encrypted email
Only plaintext emails can be encrypted, trying to send HTML emails using Email::send() and in turn GPGMailer::sendHTML() 
triggers a warning and sends the email using GPGMailer::sendPlain().

File attachments are encrypted, we noticed during testing that after encryption the size of the attachment was about 30% 
larger.

```
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

### Creating GPG keys

#### Sender or signing key
- Install GPG Keychain Access
- Create private/public pair for desired email address
- Export ASCII armored keys
- Import the public and private keys and specify the keyrings:
```
gpg --no-default-keyring --keyring ./pubring.gpg --secret-keyring ./secring.gpg --import /path/HJK568.asc
```
- Provide the public key to the recipient so that they can verify the signature on the email

#### Recipeient or encrypting key
- Get the ASCII armored public key of the recipient
- Import the public key into the keyring:
```
gpg --no-default-keyring --keyring ./pubring.gpg --import /path/HJK597C00.asc
```
- [Trust the key](https://www.gnupg.org/gph/en/manual/x334.html#AEN345)
```
gpg --no-default-keyring --keyring ./pubring.gpg --edit-key rebelalliance+privacy@silverstripe.com
trust
```



