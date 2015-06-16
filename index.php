<?php

class DBChangePasswordPlugin extends \RainLoop\Plugins\AbstractPlugin
{
	public function Init()
	{
		$this->addHook('main.fabrica', 'MainFabrica');
	}

	/**
	 * @param string $sName
	 * @param mixed $oProvider
	 */
	public function MainFabrica($sName, &$oProvider)
	{
		switch ($sName)
		{
			case 'change-password':

				include_once __DIR__.'/ChangePasswordDBDriver.php';

				$oProvider = new ChangePasswordDBDriver();

				$oProvider
					->SetDBType($this->Config()->Get('plugin', 'dbtype', 'psql'))
					->SetDBHost($this->Config()->Get('plugin', 'dbhost', ''))
					->SetDBPort((int) $this->Config()->Get('plugin', 'port', 5432))
					->SetDBUser($this->Config()->Get('plugin', 'dbuser', ''))
					->SetDBPassword($this->Config()->Get('plugin', 'dbpass', ''))
					->SetDBName($this->Config()->Get('plugin', 'dbname', ''))
					->SetDBQuery($this->Config()->Get('plugin', 'dbquery', ''))
					->SetCryptAlgo($this->Config()->Get('plugin', 'cryptalgo', 'md5'))
					->SetEncodeAlgo($this->Config()->Get('plugin', 'encodealgo', 'hex'))
					->SetAllowedEmails(\strtolower(\trim($this->Config()->Get('plugin', 'allowed_emails', ''))))
					->SetLogger($this->Manager()->Actions()->Logger())
				;

				break;
		}
	}

	/**
	 * @return array
	 */
	public function configMapping()
	{
		return array(
			\RainLoop\Plugins\Property::NewInstance('dbtype')
				->SetLabel('Database Type')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::SELECTION)
				->SetDefaultValue(array('mysql', 'pgsql')),

			\RainLoop\Plugins\Property::NewInstance('dbhost')
				->SetLabel('Database Host')
				->SetDefaultValue('127.0.0.1'),

			\RainLoop\Plugins\Property::NewInstance('dbport')
				->SetLabel('Database Port')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::INT)
				->SetDefaultValue(5432),

			\RainLoop\Plugins\Property::NewInstance('dbuser')
				->SetLabel('Database User'),

			\RainLoop\Plugins\Property::NewInstance('dbpass')
				->SetLabel('Database Password')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::PASSWORD),

			\RainLoop\Plugins\Property::NewInstance('dbname')
				->SetLabel('Database Name'),

			\RainLoop\Plugins\Property::NewInstance('dbquery')
				->SetLabel('Database Query')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDefaultValue("UPDATE users SET password= :password WHERE email=:email")
				->SetDescription('Database query to use. Parameter bindings: :email, :password, :local, :domain'),

			\RainLoop\Plugins\Property::NewInstance('cryptalgo')
				->SetLabel('Encryption Algorithm')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::SELECTION)
				->SetDefaultValue(array('cleartext', 'sha512', 'md5')),

			\RainLoop\Plugins\Property::NewInstance('encodealgo')
				->SetLabel('Encoding Algorithm')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::SELECTION)
				->SetDefaultValue(array('binary', 'base64', 'hex')),

			\RainLoop\Plugins\Property::NewInstance('allowed_emails')->SetLabel('Allowed emails')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('Allowed emails, space as delimiter, wildcard supported. Example: user1@domain1.net user2@domain1.net *@domain2.net')
				->SetDefaultValue('*')
		);
	}
}
