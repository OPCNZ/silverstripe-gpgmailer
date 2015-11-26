<?php

require_once 'Crypt/GPG.php';

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
class GPGMailer extends Mailer {

	/**
	 * Encryption key
	 *
	 * @var string
	 */
	private static $encrypt_key;

	/**
	 * Signing key
	 *
	 * @var string
	 */
	private static $sign_key;

	/**
	 * Signing passphrase
	 *
	 * @var string
	 */
	private static $sign_passphrase;

	/**
	 * Options for Crypt_GPG
	 *
	 * @see Crypt_GPGAbstract::__construct() for available options
	 * @var array
	 */
	private static $options = array();

	/**
	 * Instance of Crypt_GPG
	 *
	 * @var Crypt_GPG
	 */
	private $gpg;

	/**
	 * Whether to sign the email also. Automatically enabled if {@link self::$sign_key} is provided.
	 *
	 * @var boolean
	 */
	private $sign = false;

	/**
	 * Set options for Crypt_GPG and add encrypting and signing keys.
	 * 
	 * @param string $encryptKey        Key identifier, usually an email address but can be fingerprint
	 * @param string $signKey           Key identifier, usually an email address but can be fingerprint
	 * @param string $signPassphrase    Optional passphrase for key required for signing
	 */
	public function __construct($encryptKey = null, $signKey = null, $signPassphrase = null) {
		parent::__construct();

		if(empty($encryptKey)) {
			$encryptKey = self::config()->encrypt_key;
		}
		if(empty($signKey)) {
			$signKey = self::config()->sign_key;
		}
		if(empty($signPassphrase)) {
			$signPassphrase = self::config()->sign_passphrase;
		}

		$options = self::config()->options;

		// Backwards compatibility - support a single nested array
		if(is_array($options[0])) {
			$options = $options[0];
		}

		// Option to override home dir and provide a relative path instead
		if (isset($options['relative_homedir'])) {
			$options['homedir'] = Director::getAbsFile($options['relative_homedir']);
			unset($options['relative_homedir']);
		}

		// Set options
		$this->gpg = new Crypt_GPG($options);

		// Add encryption key
		$this->gpg->addEncryptKey($encryptKey);

		// Add signing key
		if (!empty($signKey)) {
			$this->gpg->addSignKey($signKey, $signPassphrase);
			$this->sign = true;
		}
	}

	/**
	 * Encrypt and send plain text email, large amount of copy paste from Mailer::sendPlain().
	 *
	 * @todo  conversion of BCC -> Bcc necessary in this method as well as sendHTML()?
	 * 
	 * @param  string  $to            To address RFC 2822 format
	 * @param  string  $from          From address RFC 2822 format
	 * @param  string  $subject       Subject line for email
	 * @param  string  $plainContent  Content for email
	 * @param  boolean $attachedFiles Indicate whether files are attached
	 * @param  array   $customheaders Custom email headers
	 * @return mixed                  Array if successful or false if unsuccessful
	 */
	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {

		// Not ensurely where this is supposed to be set, but defined it false for now to remove php notices
		$plainEncoding = false; 

		if ($customheaders && is_array($customheaders) == false) {
			user_error("Could not send mail, improper custom headers: $customheaders", E_USER_WARNING);
			return false;
		}
	
		// If the subject line contains extended characters, we must encode it
		$subject = Convert::xml2raw($subject);
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

		// Make the plain text part
		$headers["Content-Type"] = "text/plain; charset=utf-8";

		// Encoding forced to 7bit
		$headers["Content-Transfer-Encoding"] = "7bit";

		// GPG encryption and signing if necessary
		if ($this->sign) {
			$plainContent = $this->gpg->encryptAndSign($plainContent);
		} else {
			$plainContent = $this->gpg->encrypt($plainContent);
		}

		// Messages with attachments are handled differently
		if ($attachedFiles) {
			// The first part is the message itself
			$fullMessage = $this->processHeaders($headers, $plainContent);
			$messageParts = array($fullMessage);

			// Include any specified attachments as additional parts
			foreach($attachedFiles as $file) {
				if(isset($file['tmp_name']) && isset($file['name'])) {
					$messageParts[] = $this->encodeFileForEmail($file['tmp_name'], $file['name']);
				} else {
					$messageParts[] = $this->encodeFileForEmail($file);
				}
			}

			// We further wrap all of this into another multipart block
			list($fullBody, $headers) = $this->encodeMultipart($messageParts, "multipart/mixed");

		// Messages without attachments do not require such treatment
		} else {
			$fullBody = $plainContent;
		}

		// Email headers
		$headers["From"] 		= $this->validEmailAddr($from);

		// Messages with the X-SilverStripeMessageID header can be tracked
		if(isset($customheaders["X-SilverStripeMessageID"]) && defined('BOUNCE_EMAIL')) {		
			$bounceAddress = BOUNCE_EMAIL;
			// Get the human name from the from address, if there is one
			if(preg_match('/^([^<>]+)<([^<>])> *$/', $from, $parts))
				$bounceAddress = "$parts[1]<$bounceAddress>";
		} else {
			$bounceAddress = $from;
		}
		
		// $headers["Sender"] 		= $from;
		$headers["X-Mailer"]	= X_MAILER;
		if(!isset($customheaders["X-Priority"])) {
			$headers["X-Priority"]	= 3;
		}
		
		$headers = array_merge((array)$headers, (array)$customheaders);

		// the carbon copy header has to be 'Cc', not 'CC' or 'cc' -- ensure this.
		if (isset($headers['CC'])) { $headers['Cc'] = $headers['CC']; unset($headers['CC']); }
		if (isset($headers['cc'])) { $headers['Cc'] = $headers['cc']; unset($headers['cc']); }

		// Send the email
		$headers = $this->processHeaders($headers);
		$to = $this->validEmailAddr($to);

		// Try it without the -f option if it fails
		if(!$result = @mail($to, $subject, $fullBody, $headers, "-f$bounceAddress"))
			$result = mail($to, $subject, $fullBody, $headers);
		
		if($result)
			return array($to,$subject,$fullBody,$headers);
			
		return false;
	}

	/**
	 * Encrypting HTML emails does not work so this method triggers a warning and sends using sendPlain() and plaintext 
	 * version of the HTML content.
	 *
	 * @param  string  $to            To address RFC 2822 format
	 * @param  string  $from          From address RFC 2822 format
	 * @param  string  $subject       Subject line for email
	 * @param  string  $plainContent  Content for email
	 * @param  boolean $attachedFiles Indicate whether files are attached
	 * @param  array   $customheaders Custom email headers
	 * @return mixed                  Array if successful or false if unsuccessful
	 */
	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false) {

		// HTML emails cannot be encrypted and create a number of issues, sendPlain() should be used instead
		trigger_error('HTML email content cannot be encrypted, only the plain text component of this email will be generated.', E_USER_WARNING);

		if (!$plainContent) {
			$plainContent = Convert::xml2raw($htmlContent);
		}

		return $this->sendPlain($to, $from, $subject, $plainContent, $attachedFiles, $customheaders);
	}

	/**
	 * Encode file for email, encryption results in ASCII armored data which removed need for base 64 encoding step.
	 *
	 * @todo  test with filename instead of array passed as $file, see Email::attachFile() and ::attachFileFromString()
	 * @todo  test with $destFilename
	 * @todo  test with disposition set to inline
	 * @todo  test with contentLocation param, see Mailer::encodeFileForEmail()
	 * 
	 * @param  mixed   $file         Array of file data including content or just string indicating filename
	 * @param  string  $destFileName Destination filename
	 * @param  string  $disposition  Disposition of attachment, inline or attachment
	 * @param  string  $extraHeaders Extra headers for attachement
	 * @return string                Contents for attachement including headers and ASCII armored file content
	 */
	public function encodeFileForEmail($file, $destFileName = false, $disposition = NULL, $extraHeaders = "") {

		if (!$file) {
			user_error("encodeFileForEmail: not passed a filename and/or data", E_USER_WARNING);
			return;
		}
		
		if (is_string($file)) {
			$file = array('filename' => $file);
			$fh = fopen($file['filename'], "rb");
			if ($fh) {
				$file['contents'] = "";
				while(!feof($fh)) $file['contents'] .= fread($fh, 10000);	
				fclose($fh);
			}
		}

		// Build headers, including content type
		if (!$destFileName) $base = basename($file['filename']);
		else $base = $destFileName;

		// Force base and MIME type for encrypted attachements
		$base = $base . '.pgp';
		$mimeType = 'application/octet-stream';

		// TODO Need to test with contentLocation param
		if (empty($disposition)) $disposition = isset($file['contentLocation']) ? 'inline' : 'attachment';

		// Encode for emailing. Only accepts binary|8bit|7bit not quoted-printable|base64
		// ASCII armored output *should* be base64 though?
		$encoding = "7bit";

		// GPG encryption and signing if necessary
		if ($this->sign) {
			$file['contents'] = $this->gpg->encryptAndSign($file['contents']);
		}
		else {
			$file['contents'] = $this->gpg->encrypt($file['contents']);
		}

		$headers =	"Content-type: $mimeType;\n\tname=\"$base\"\n".
			"Content-Transfer-Encoding: $encoding\n".
			"Content-Disposition: $disposition;\n\tfilename=\"$base\"\n";
		
		// TODO Need to test with contentLocation param
		if ( isset($file['contentLocation']) ) $headers .= 'Content-Location: ' . $file['contentLocation'] . "\n" ;
		
		$headers .= $extraHeaders . "\n";
		return $headers . $file['contents'];
	}

	/**
	 * Handle the renaming of the validEmailAddr method in silverstripe-framework 3.2.0.
	 * Maintains backwards compatibility with silverstripe-framework 3.1.
	 *
	 * @see Mailer::validEmailAddress
	 */
	public function validEmailAddr($address) {
		if(method_exists('Mailer', 'validEmailAddr')) {
			return parent::validEmailAddr($address);
		} elseif(method_exists('Mailer', 'validEmailAddress')) {
			return parent::validEmailAddress($address);
		} else {
			throw new Exception('validEmailAddr (or validEmailAddress) method not found on Mailer');
		}
	}

}