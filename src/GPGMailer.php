<?php

namespace OPCNZ\GPGMailer;

use Exception;
use InvalidArgumentException;
use Nightjar\SwiftSignerCryptGPG;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\SwiftMailer;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use Swift_Mailer;

/**
 * Mailer that encrypts contents of email using GPG. Encrypting HTML is not implemented, quite difficult and requires
 * a very simple HTML template that can be encrypted and re-wrapped in body tags.
 *
 * Necessary to provide keyring files via Crypt_GPG options in YAML.
 *
 * @todo  HTML encryption if possible, look into PGP/MIME
 * @todo  Ability to add additional encryption and signing keys
 * @todo  correct headers for Content-Transfer-Encoding, should be base64 for ASCII armor? Only accepts binary|8bit|7bit not quoted-printable|base64
 *        http://en.wikipedia.org/wiki/MIME#Content-Transfer-Encoding
 *        http://www.techopedia.com/definition/23150/ascii-armor
 *        https://tools.ietf.org/html/rfc3156
 *        http://docs.roguewave.com/sourcepro/11.1/html/protocolsug/10-1.html
 *        https://www.gnupg.org/documentation/manuals/gnupg/Input-and-Output.html
 *        "Base64 is a group of similar binary-to-text encoding schemes that represent binary data in an ASCII string format by translating it into a radix-64 representation."
 * @todo  Content-Type header to include protocol="application/pgp-encrypted" https://tools.ietf.org/html/rfc3156
 *
 */
class GPGMailer extends SwiftMailer
{
    private static $dependencies = [
        'SwiftMailer' => '%$' . Swift_Mailer::class,
    ];

    /**
     * Options for Crypt_GPG
     *
     * @see Crypt_GPGAbstract::__construct() for available options
     *
     * @config
     * @var array
     */
    private static $default_options = [];

    /**
     * Options provided to Crypt_GPG
     *
     * @var array
     */
    private $cryptGPGOptions = [];

    /**
     * Encryption key for the email
     *
     * @var boolean
     */
    private $encryptKey;

    /**
     * Whether to sign the email with this key also
     *
     * @var boolean
     */
    private $signKey;

    /**
     * Passphrase for signing key, if needed
     *
     * @var boolean
     */
    private $signKeyPassphrase;

    /**
     * Set options for Crypt_GPG and add encrypting and signing keys.
     *
     * @param string $encryptKey        Key identifier, usually an email address but can be fingerprint
     * @param string $signKey           Key identifier, usually an email address but can be fingerprint
     * @param string $signKeyPassphrase Passphrase required for the signKey
     * @param array  $options           Option set {@see Crypt_GPGAbstract::__construct}
     * @throws InvalidArgumentException
     */
    public function __construct($encryptKey = '', $signKey = '', $signKeyPassphrase = '', $options = [])
    {
        $this->setCryptGPGOptions($options);
        $this->setEncryptKey($encryptKey ?: Environment::getEnv('GPGMAILER_ENCRYPT_KEY'));
        $this->setSignKey($signKey ?: Environment::getEnv('GPGMAILER_SIGN_KEY'));
        $this->setSignKeyPassphrase($signKeyPassphrase ?: Environment::getEnv('GPGMAILER_SIGN_KEY_PASSPHRASE'));
    }

    /**
     * Sets the encryption key for this mailer.
     *
     * @param string|null $encryptionKey
     * @return $this
     */
    public function setEncryptKey($encryptionKey = null)
    {
        if (!isset($encryptionKey)) {
            throw new InvalidArgumentException('Encryption key not defined');
        }
        $this->encryptKey = $encryptionKey;
        return $this;
    }

    /**
     * Set signing key for this mailer. Optional.
     *
     * @param string|null $signingKey
     * @return $this
     */
    public function setSignKey($signingKey = null)
    {
        // set explicitly to null as empty string may be used as an invalid key
        $this->signKey = $signingKey === '' ? null : $signingKey;
        return $this;
    }

    /**
     * Set sign key passphrase if applicable
     *
     * @param string|null $passphrase
     * @return $this
     */
    public function setSignKeyPassphrase($passphrase = null)
    {
        // set explicitly to null as empty string may be used as an invalid passphrase
        $this->signKeyPassphrase = $passphrase === '' ? null : $passphrase;
        return $this;
    }

    /**
     * Set options for Crypt_GPG.
     *
     * Some options are always overridden if environment variables are present. This allows for ease of set up in
     * testing envrionments, providing assurance of settings.
     *
     * @param array $options Option set. {@see Crypt_GPGAbstract::__construct} for available options
     * @return $this
     */
    public function setCryptGPGOptions(array $options = []): self
    {
        $options = $options ?: $this->config()->get('default_options');

        if ($homeDir = Environment::getEnv('GPGMAILER_HOMEDIR')) {
            // Environment variables should override Configuration system
            $options['homedir'] = $homeDir;
        } else if (isset($options['relative_homedir'])) {
            // Option to override home dir and provide a relative path instead
            $options['homedir'] = Director::getAbsFile($options['relative_homedir']);
            unset($options['relative_homedir']);
        }

        $this->cryptGPGOptions = $options;

        return $this;
    }

    /**
     * @param Email $message
     * @return bool Whether the sending was "successful" or not
     */
    public function send($message)
    {
        /** @var Swift_Message $swiftMessage */
        $swiftMessage = $message->getSwiftMessage();
        $swiftMessage->attachSigner(new SwiftSignerCryptGPG(
            $this->encryptKey,
            $this->signKey,
            $this->signKeyPassphrase,
            $this->cryptGPGOptions
        ));
        $failedRecipients = array();
        $result = $this->sendSwift($swiftMessage, $failedRecipients);
        $message->setFailedRecipients($failedRecipients);

        return $result != 0;
    }
}
