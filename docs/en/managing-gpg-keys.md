# Managing GPG keys

GUI tools are available to help with generating keys and managing keyrings.

 - [GPG Keychain](https://gpgtools.org/) for macOS.
 - [GPG4Win](http://www.gpg4win.org) for Windows.

## Creating GPG keys

### Recipient's key (for encrypting)

 - Get the ASCII armored public key of the recipient
 - Import the public key into the keyring on the server receiving the email:

```bash
gpg --no-default-keyring --keyring ./pubring.gpg --import /path/HJK597C00.asc
```
- [Trust the key](https://www.gnupg.org/gph/en/manual/x334.html#AEN345)

```bash
gpg --no-default-keyring --keyring ./pubring.gpg --edit-key recipient@example.com
trust
```

### Sender's key (optional, for signing)

 - Create private/public pair for desired email address (using `gpg` command or a keychain GUI)
 - Export ASCII armored keys
 - Provide the public key to the recipient so that they can verify the signature on the email
 - Import the public and private keys into the keyrings on your web server:

```bash
gpg --no-default-keyring --keyring ./pubring.gpg --secret-keyring ./secring.gpg --import /path/HJK568.asc
```
