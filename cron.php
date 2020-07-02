<?php

class AUTOSUSPEND_Cron extends OW_Cron
{
    const USER_PROFILE = 'user_join';
    const USER_STATUS = 'user-status';
    const FORUM_POST = 'forum-post';
    const FORUM_TOPIC = 'forum-topic';
    const GROUP = 'group';
    const PHOTO = 'photo_comments';
    const VIDEO = 'video_comments';
    const COMMENT = 'comment';

    private $userIdsOrganizedByReportingUserId = [];

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
     * Gets a list of flag-types to check, then a count of number of times each flagged user has been flagged.
     * Calls suspendUser() for users with more than X flags.
     *  
     */
    public function autoSuspend()
    {
        $typesOfFlagsBeingChecked = json_decode(OW::getConfig()->getValue('autosuspend', 'flags_to_check'));

        if ( !$this->checkWhetherToRun($typesOfFlagsBeingChecked) )
        {
            return;
        }

        $listOfAllFlaggedIds = [];

        foreach ($typesOfFlagsBeingChecked as $typeOfFlag) 
        {
            $listOfAllFlaggedIds = array_merge( $listOfAllFlaggedIds, $this->getListOfUserIdsForFlagType($typeOfFlag) );
        }

        $listOfUniqueIds = array_unique($listOfAllFlaggedIds);
        $numberOfFlags = array_count_values($listOfAllFlaggedIds);

        $maxFlags = OW::getConfig()->getValue('autosuspend', 'max_flags');

        foreach ($listOfUniqueIds as $userId) 
        {
            if ( $numberOfFlags[$userId] >= $maxFlags ) 
            {
                $this->suspendUser($userId);
            }
        }
    }

    /*
     * Checks whether autosuspend should run
     * Returns FALSE if there are no flags, no new flags, or no flag-types to check
     * Otherwise, returns TRUE
     * 
     * @param array $typesOfFlagsBeingChecked
     * @return boolean
     * 
     */
    private function checkWhetherToRun($typesOfFlagsBeingChecked) {
        
        if (empty($typesOfFlagsBeingChecked))
        {
            return false;
        }

        $flags = BOL_FlagService::getInstance()->findFlagsByEntityTypeList($typesOfFlagsBeingChecked);
        
        if (empty($flags))
        {
            return false;
        }

        if (!$this->newFlagsExist($flags)) 
        {
            return false;
        }

        return true;
    }

    /*
     * Checks whether there are any new flags in database, since last check
     * Returns FALSE if there are no new flags
     * Returns TRUE if there are new flags
     * 
     * @param array $flags
     * @return boolean
     * 
     */
    private function newFlagsExist($flags)
    {
        $searchableArray = json_decode(json_encode($flags), true);

        $idOfMostRecentFlag = max(array_column($searchableArray, 'id'));
        $idOfMostRecentAlreadyCheckedFlag = OW::getConfig()->getValue('autosuspend', 'most_recent_flag');

        if ($idOfMostRecentAlreadyCheckedFlag >= $idOfMostRecentFlag) {
            return false;
        } else {
            OW::getConfig()->saveConfig('autosuspend', 'most_recent_flag', $idOfMostRecentFlag);
            return true;
        }
    }

    /*
     * Accepts a flagtype & returns an array of all user-IDs flagged with that flagtype
     * If the same user-ID has been flagged by more than one 1 person, that user-ID will be included multiple times
     * 
     * @param string $flagType
     * @return array $listOfFlaggedIds
     * 
     */
    private function getListOfUserIdsForFlagType($flagType) 
    {
        $flags = BOL_FlagService::getInstance()->findFlagsByEntityTypeList(array($flagType));

        if (empty($flags)) 
        {
            return [];
        }

        $listOfFlaggedIds = [];
        
        foreach ($flags as $flag) 
        {
            (int)$userId = $this->findUserId( $flagType, $flag->entityId );
            $reporterId = $flag->userId;

            if (empty($userId) || empty($reporterId)) 
            {
                break;
            } 

            if ($this->isNotDuplicateFlagsFromSameUser($reporterId, $userId)) 
            {
                array_push($listOfFlaggedIds, $userId);
            }
        }

        return $listOfFlaggedIds;
    }

    /*
     * Prevent $userId from being suspended due to multiple reports from a single $reporterId 
     * 
     * Check $userIdsOrganizedByReportingUserId.
     * If entry does not exist, create a new entry in $userIdsOrganizedByReportingUserId 
     * for that $reporterId & $userId pair, and return TRUE.
     * If entry already exists, then $reporterId has already reported $userId. Return 
     * FALSE.
     * 
     * @param integer $reporterId
     * @param integer $userId
     * @return boolean
     * 
     */
    private function isNotDuplicateFlagsFromSameUser($reporterId, $userId)
    {
        if( !isset($this->userIdsOrganizedByReportingUserId[$reporterId][$userId]) )
        {
            $this->userIdsOrganizedByReportingUserId[$reporterId][$userId] = 1;
            return true;
        } else {
            return false;
        }
    }

    /* 
     * Accepts the flagtype and the entityID for a specific flag, returns the corresponding userId
     * 
     * @param string $flagType
     * @param integer $entityId
     * @return integer 
     * 
     */
    private function findUserId($flagType, $entityId) 
    {
        switch($flagType) 
        {
            case self::USER_PROFILE:
                return $entityId;
            case self::USER_STATUS:
                return NEWSFEED_BOL_Service::getInstance()->findStatusDtoById( $entityId )->feedId;
            case self::FORUM_POST:
                return FORUM_BOL_ForumService::getInstance()->findPostById( $entityId )->userId;
            case self::FORUM_TOPIC:
                return FORUM_BOL_ForumService::getInstance()->findTopicById( $entityId )->userId;
            case self::GROUP:
                return GROUPS_BOL_Service::getInstance()->findGroupById( $entityId )->userId;
            case self::PHOTO:
                return PHOTO_BOL_PhotoService::getInstance()->findPhotoOwner( $entityId );
            case self::VIDEO:
                return VIDEO_BOL_ClipService::getInstance()->findClipOwner( $entityId );
            case self::COMMENT:
                return BOL_CommentService::getInstance()->findComment( $entityId )->userId;    
        }
    }

    /* 
     * Suspends user
     *  
     * @param integer $userId
     * 
     */
    private function suspendUser($userId)
    {
        $isModerator = BOL_AuthorizationService::getInstance()->isModerator($userId);
        $modsShouldBeSuspended = OW::getConfig()->getValue('autosuspend', 'suspend_mods');

        if (!$isModerator || ($isModerator && $modsShouldBeSuspended))
        {
            $suspendMessage = OW::getLanguage()->text("autosuspend", "suspension_reason");
            BOL_UserService::getInstance()->suspend($userId, $suspendMessage);
        } 
    }
}