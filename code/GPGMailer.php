<?php

require_once 'Crypt/GPG.php';

class GPGMailer extends Mailer {

	private $options = array();

	private $gpg;

	public function __construct() {
		parent::__construct();

		// Set options
		$this->setOptions();
		$this->gpg = new Crypt_GPG($this->options);		
	}

	private function setOptions() {

		$options = GPGMailer::config()->options;
		if (isset($options[0]) && is_array($options[0])) {
			$this->options = $options[0];
		}

		// Option to override home dir and provide a relative path instead
		if (isset($this->options['relative_homedir'])) {
			$this->options['homedir'] = Director::getAbsFile($this->options['relative_homedir']);
			unset($this->options['relative_homedir']);
		}
	}

	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {

		// TODO: Check that the to address is an acceptable encrypt address
		// TODO: Check that the from address is an acceptable signing address
		try {

			// Encrypt email
			$encryptKey = $this->getEncryptKey($to);
			if (!$encryptKey) {
				throw new Exception("Could not find valid key for encrypting using {$to}.");
			}

			$this->gpg->addEncryptKey($encryptKey);
			$encryptedContent = $this->gpg->encrypt($plainContent);

			// Sign email
			$signingKey = $this->getSigningKey($from);
			if (!$signingKey) {
				throw new Exception("Could not find valid key for signing using {$from}.");
			}

			$this->gpg->addSignKey($signingKey['email'], $signingKey['password']);
			$signedContent = $this->gpg->sign($encryptedContent, Crypt_GPG::SIGN_MODE_CLEAR);

			return parent::sendPlain($to, $from, $subject, $signedContent, $attachedFiles, $customheaders);
		}
		catch (Exception $e) {
			SS_Log::log(new Exception(print_r('Failed to send encrypted email: ' . $e->getMessage(), true)), SS_Log::ERR);
		}
	}

	private function getEncryptKey($address) {

		$key = null;
		if (in_array($address, $this->config()->encrypt_keys)) {
			$key = $address;
		}
		return $key;
	}

	private function getSigningKey($address) {

		$key = array();

		foreach ($this->config()->signing_keys as $values) {
			if ($values['email'] == $address) {
				$key = $values;
				break;
			}
		}
		return $key;
	}

}