# Testing in development

## Set up

You will need to generate a keyring that is accessible by the web user (e.g. `www-data` or `http`).
It is advisable to keep these files outside the project root.

You will need to set up a keys for:
 - The website, as the sender - used for signing. E.g. `your+website@localhost.test`
 - The recipient, used by the server to encrypt the message. `you+recipient@localhost.test`


```sh
mkdir /srv/http/.gnupg
sudo chown http /srv/http/.gnupg
sudo -u http gpg2 --homedir /srv/http/.gnupg --gen-key
# follow key generation prompts and ensure that it is successful
```
## Configuration

Add to the enviornment via `export`, the web server config, or the `.env` file:

```sh
# GPGMailer dev setup
GPGMAILER_ENCRYPT_KEY=you+recipient@silverstripe.com # encrypt so only recipient can read
GPGMAILER_SIGN_KEY=your+website@silverstripe.com # verify who it was sent from
GPGMAILER_SIGN_KEY_PASSPHRASE=password
GPGMAILER_HOMEDIR="/srv/http/.gnupg"
```

# Testing

Use a tool such as [Mailhog](https://github.com/mailhog/MailHog/releases/tag/v1.0.0) to be able to easily capture emails.
You can of course configure a client to read the mail for real.
Messages can be read via CLI with `gpg2 --decrypt`, passing the filename of a saved message as the operand.
