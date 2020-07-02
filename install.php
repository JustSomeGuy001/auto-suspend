<?php

$path = OW::getPluginManager()->getPlugin('autosuspend')->getRootDir() . 'langs.zip';
BOL_LanguageService::getInstance()->importPrefixFromZip($path, 'autosuspend');

OW::getPluginManager()->addPluginSettingsRouteName('autosuspend', 'autosuspend.admin');

OW::getConfig()->addConfig('autosuspend', 'max_flags', 5, 'Max flags before suspending.');
OW::getConfig()->addConfig('autosuspend', 'cron_frequency', 5, 'Cron frequency for checking flags.');
OW::getConfig()->addConfig('autosuspend', 'suspend_mods', false, 'Whether mods should be suspended.');
OW::getConfig()->addConfig('autosuspend', 'flags_to_check', null, 'The list of flag types checked.' );
OW::getConfig()->addConfig('autosuspend', 'most_recent_flag', 0, 'Flag Id of most recent checked flag.');
