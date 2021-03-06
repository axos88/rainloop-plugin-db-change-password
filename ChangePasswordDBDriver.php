<?php

if (!function_exists('bindValueIfExists')) {
	require_once('bindValueIfExists.php');
}

class ChangePasswordDBDriver implements \RainLoop\Providers\ChangePassword\ChangePasswordInterface
{
	/**
	 * @var string
	 */
	private $sAllowedEmails;

	private $sDBType;
	private $sDBHost;
	private $iDBPort;
	private $sDBUser;
	private $sDBPassword;
	private $sDBName;
	private $sDBQuery;
	private $sCryptAlgo;
	private $sEncodeAlgo;

	private $oLogger;


	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetLogger($oLogger)
	{
		$this->oLogger = $oLogger;
		return $this;
	}


	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBType($sDBType)
	{
		$this->sDBType = $sDBType;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBHost($sDBHost)
	{
		$this->sDBHost = $sDBHost;
		return $this;
	}

	/**
	 * @param int $iPort
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBPort($iDBPort)
	{
		$this->iDBPort = (int) $iDBPort;
		return $this;
	}


	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBUser($sDBUser)
	{
		$this->sDBUser = $sDBUser;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBPassword($sDBPassword)
	{
		$this->sDBPassword = $sDBPassword;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBQuery($sDBQuery)
	{
		$this->sDBQuery = $sDBQuery;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetCryptAlgo($sDBCryptAlgo)
	{
		$this->sCryptAlgo = $sDBCryptAlgo;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetEncodeAlgo($sDBEncodeAlgo)
	{
		$this->sEncodeAlgo = $sDBEncodeAlgo;
		return $this;
	}

	/**
	 * @param string $sHost
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetDBName($sDBName)
	{
		$this->sDBName = $sDBName;
		return $this;
	}

	/**
	 * @param string $sAllowedEmails
	 *
	 * @return \ChangePasswordPoppassdDriver
	 */
	public function SetAllowedEmails($sAllowedEmails)
	{
		$this->sAllowedEmails = $sAllowedEmails;
		return $this;
	}

	/**
	 * @param \RainLoop\Account $oAccount
	 *
	 * @return bool
	 */
	public function PasswordChangePossibility($oAccount)
	{
		return $oAccount && $oAccount->Email() &&
			\RainLoop\Plugins\Helper::ValidateWildcardValues($oAccount->Email(), $this->sAllowedEmails);
	}

	/**
	 * @param \RainLoop\Account $oAccount
	 * @param string $sPrevPassword
	 * @param string $sNewPassword
	 *
	 * @return bool
	 */
	public function ChangePassword(\RainLoop\Account $oAccount, $sPrevPassword, $sNewPassword)
	{
		$bResult = false;

		if (0 < \strlen($sNewPassword))
		{
			try
			{
				$sDsn = $this->sDBType . ':host='.$this->sDBHost.';port='.$this->iDBPort.';dbname='.$this->sDBName;

				$oPdo = new \PDO($sDsn, $this->sDBUser, $this->sDBPassword);
				$oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

				$sPasswordHash = $this->hashPassword($sNewPassword);
				$sEncodedPasswordHash = $this->encodePassword($sPasswordHash);

				if (0 < \strlen($sEncodedPasswordHash))
				{
					$oStmt = $oPdo->prepare($this->sDBQuery);

					$email = explode('@', $oAccount->Email());

					bindValueIfExists($oStmt, $this->sDBQuery, 'email', join('@', $email));
					bindValueIfExists($oStmt, $this->sDBQuery, 'local', $email[0]);
					bindValueIfExists($oStmt, $this->sDBQuery, 'domain', $email[1]);
					bindValueIfExists($oStmt, $this->sDBQuery, 'password', $sEncodedPasswordHash);

					$bResult = (bool) $oStmt->execute();
				}
				else
				{
					if ($this->oLogger)
					{
						$this->oLogger->Write('DBChangePassword: Encrypted password is empty?!',
							\MailSo\Log\Enumerations\Type::ERROR);
					}
				}

				$oPdo = null;
			}
			catch (\Exception $oException)
			{
				file_put_contents('/tmp/log.log', print_r($oException, true), FILE_APPEND);

				if ($this->oLogger)
				{
					$this->oLogger->WriteException($oException);
				}
			}
		}

		return $bResult;
	}

	private function encodePassword($sPasswordHash) {
		switch ($this->sEncodeAlgo) {
			case 'base64':
				return base64_encode($sPasswordHash);
			case 'hex':
				return bin2hex($sPasswordHash);
			case 'binary':
				return $sPasswordHash;
			default:
				return '';
		}
	}

	private function hashPassword($sPassword)
	{
		switch ($this->sCryptAlgo)
		{
			case 'cleartext':
				return $sPassword;
			default:
				return hash($this->sCryptAlgo, $sPassword, true);
		}
	}
}
