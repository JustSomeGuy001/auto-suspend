<?php

$path = OW::getPluginManager()->getPlugin('autosuspend')->getRootDir() . 'langs.zip';
BOL_LanguageService::getInstance()->importPrefixFromZip($path, 'autosuspend');

OW::getPluginManager()->addPluginSettingsRouteName('autosuspend', 'autosuspend.admin');

OW::getConfig()->addConfig('autosuspend', 'max_flags', 5);
OW::getConfig()->addConfig('autosuspend', 'cron_frequency', 5);
