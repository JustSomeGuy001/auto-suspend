<?php


class AUTOSUSPEND_Cron extends OW_Cron
{
    public function __construct()
    {
        parent::__construct();
        $cronFrequency = OW::getConfig()->getValue('autosuspend', 'cron_frequency');

        $this->addJob('autoSuspend', $cronFrequency);
    }

    public function run()
    {
        
    }

    /*
     * Checks flags in database and suspends users with more than X flags
     * 
     * IN: None
     * OUT: No return
     *  
     */
    public function autoSuspend()
    {
        $flags = BOL_FlagService::getInstance()->findFlagsByEntityTypeList(array('user_join'));

        if (empty($flags)) 
        {
            return;
        }

        $listOfFlaggedIds = [];

        foreach ($flags as $flag) 
        {
            array_push($listOfFlaggedIds, $flag->entityId);
        } 

        $uniqueIds = array_unique($listOfFlaggedIds);
        $counts = array_count_values($listOfFlaggedIds);

        $maxFlags = OW::getConfig()->getValue('autosuspend', 'max_flags');
        $suspendMessage = OW::getLanguage()->text("autosuspend", "suspension_reason");

        foreach ($uniqueIds as $uniqueId) 
        {
            if ( $counts[$uniqueId] >= $maxFlags ) 
            {
                BOL_UserService::getInstance()->suspend($uniqueId, $suspendMessage);
            }
        }
    }
}