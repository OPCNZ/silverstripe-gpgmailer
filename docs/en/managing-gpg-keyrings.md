# Managing GPG keys

## Creating GPG keys

### Sender or signing key
- Install a keychain manager (see below) 
- Create private/public pair for desired email address
- Export ASCII armored keys
- Import the public and private keys and specify the keyrings on your web server:

```bash
gpg --no-default-keyring --keyring ./pubring.gpg --secret-keyring ./secring.gpg --import /path/HJK568.asc
```
- Provide the public key to the recipient so that they can verify the signature on the email

### Recipient or encrypting key
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

## Managing your keyrings via GPG Keychain
For OSX, a useful tool for managing your keys and keyrings is [GPG Keychain](https://gpgtools.org/).

For Windows, you can use [GPG4Win](http://www.gpg4win.org)