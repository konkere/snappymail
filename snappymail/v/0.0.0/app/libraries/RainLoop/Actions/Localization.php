<?php

namespace RainLoop\Actions;

trait Localization
{
	public function GetLanguage(bool $bAdmin = false): string
	{
		$oConfig = $this->Config();
		if ($bAdmin) {
			$sLanguage = $oConfig->Get('webmail', 'language_admin', 'en');
		} else {
			$sLanguage = $oConfig->Get('webmail', 'language', 'en');
			if ($oAccount = $this->getAccountFromToken(false)) {
				if ($oConfig->Get('webmail', 'allow_languages_on_settings', true)
				 && $this->GetCapa(false, \RainLoop\Enumerations\Capa::SETTINGS, $oAccount)
				 && ($oSettings = $this->SettingsProvider()->Load($oAccount))) {
					$sLanguage = $oSettings->GetConf('Language', $sLanguage);
				}
			} else if ($oConfig->Get('login', 'allow_languages_on_login', true) && $oConfig->Get('login', 'determine_user_language', true)) {
				$sLanguage = $this->ValidateLanguage($this->detectUserLanguage($bAdmin), $sLanguage, false);
			}
		}
		return $this->ValidateLanguage($sLanguage, '', $bAdmin) ?: 'en';
	}

	public function ValidateLanguage(string $sLanguage, string $sDefault = '', bool $bAdmin = false, bool $bAllowEmptyResult = false): string
	{
		$aLang = \SnappyMail\L10n::getLanguages($bAdmin);

		$aHelper = array(
			'ar' => 'ar-SA',
			'cs' => 'cs-CZ',
			'no' => 'nb-NO',
			'ua' => 'uk-UA',
			'cn' => 'zh-CN',
			'zh' => 'zh-CN',
			'tw' => 'zh-TW',
			'fa' => 'fa-IR'
		);

		$sLanguage = isset($aHelper[$sLanguage]) ? $aHelper[$sLanguage] : \strtr($sLanguage, '_', '-');
		$sDefault  = isset($aHelper[$sDefault])  ? $aHelper[$sDefault]  : \strtr($sDefault, '_', '-');

		if (\in_array($sLanguage, $aLang)) {
			return $sLanguage;
		}

		$sLangCountry = \preg_replace_callback('/-([a-zA-Z]{2})$/', function ($aData) {
			return \strtoupper($aData[0]);
		}, $sLanguage);
		if (\in_array($sLangCountry, $aLang)) {
			return $sLangCountry;
		}

		if (\in_array($sDefault, $aLang)) {
			return $sDefault;
		}

		if ($bAllowEmptyResult) {
			return '';
		}

		$sResult = $this->Config()->Get('webmail', $bAdmin ? 'language_admin' : 'language', 'en');
		return \in_array($sResult, $aLang) ? $sResult : 'en';
	}

	private function getUserLanguagesFromHeader(): array
	{
		$aResult = $aList = array();
		$sAcceptLang = \strtolower($this->Http()->GetServer('HTTP_ACCEPT_LANGUAGE', 'en'));
		if (!empty($sAcceptLang) && \preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $sAcceptLang, $aList)) {
			$aResult = \array_combine($aList[1], $aList[2]);
			foreach ($aResult as $n => $v) {
				$aResult[$n] = $v ? $v : 1;
			}

			\arsort($aResult, SORT_NUMERIC);
		}

		return $aResult;
	}

	public function detectUserLanguage(bool $bAdmin): string
	{
		$sResult = '';
		$aLangs = $this->getUserLanguagesFromHeader();

		foreach (\array_keys($aLangs) as $sLang) {
			$sLang = $this->ValidateLanguage($sLang, '', $bAdmin, true);
			if (!empty($sLang)) {
				$sResult = $sLang;
				break;
			}
		}

		return $sResult;
	}

	public function StaticI18N(string $sKey): string
	{
		static $sLang = null;
		static $aLang = null;

		if (null === $sLang) {
			$sLang = $this->GetLanguage();
		}

		if (null === $aLang) {
			$sLang = $this->ValidateLanguage($sLang, 'en');
			$aLang = \SnappyMail\L10n::load($sLang, 'static');
			$this->Plugins()->ReadLang($sLang, $aLang);
		}

		return $aLang[$aKey] ?? $sKey;
	}
}
