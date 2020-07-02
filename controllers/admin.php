<?php

class AUTOSUSPEND_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    const USER_PROFILE = 'user_join';
    const USER_STATUS = 'user-status';
    const FORUM_POST = 'forum-post';
    const FORUM_TOPIC = 'forum-topic';
    const GROUP = 'group';
    const PHOTO = 'photo_comments';
    const VIDEO = 'video_comments';
    const COMMENT = 'comment';

    public function admin()
    {

        $language = OW::getLanguage();

        // Set page title and heading
        $this->setPageTitle($language->text("autosuspend", "admin_page_title"));
        $this->setPageHeading($language->text("autosuspend", "admin_page_heading"));

        // Assign variables
        $maxFlags = OW::getConfig()->getValue('autosuspend', 'max_flags');
        $cronFrequency = OW::getConfig()->getValue('autosuspend', 'cron_frequency');
        $suspendModerator = OW::getConfig()->getValue('autosuspend', 'suspend_mods');
        $suspendMessage = OW::getLanguage()->text("autosuspend", "suspension_reason");
        $flagsCurrentlyBeingChecked = json_decode(OW::getConfig()->getValue('autosuspend', 'flags_to_check'));
        
        // Create array of flag types being checked
        $flagTypes = [];
        if (!empty($flagsCurrentlyBeingChecked))
        {
            foreach ($flagsCurrentlyBeingChecked as $flagCurrentlyBeingChecked) 
            {
                switch ($flagCurrentlyBeingChecked) 
                {
                    case self::USER_PROFILE:
                        array_push($flagTypes, $language->text("autosuspend", "user_profiles_label"));
                        break;
                    case self::USER_STATUS:
                        array_push($flagTypes, $language->text("autosuspend", "status_updates_label"));
                        break;
                    case self::FORUM_TOPIC:
                        array_push($flagTypes, $language->text("autosuspend", "forum_posts_label"));
                        break;
                    case self::FORUM_POST:
                        array_push($flagTypes, $language->text("autosuspend", "forum_replies_label"));
                        break;
                    case self::GROUP:
                        array_push($flagTypes, $language->text("autosuspend", "groups_label"));
                        break;
                    case self::PHOTO:
                        array_push($flagTypes, $language->text("autosuspend", "photos_label"));
                        break;
                    case self::VIDEO:
                        array_push($flagTypes, $language->text("autosuspend", "videos_label"));
                        break;
                    case self::COMMENT:
                        array_push($flagTypes, $language->text("autosuspend", "comments_label"));
                        break;
                }
            }
        }

        // Assignments for use in HTML page
        $this->assign('maxFlags', $maxFlags);
        $this->assign('cronFrequency', $cronFrequency);
        $this->assign('suspendMessage', $suspendMessage);
        $this->assign('flagTypes', $flagTypes);
        $this->assign('suspendModerator', $suspendModerator);
 
        // Create new form
        $form = new Form('update_settings');
        $this->addForm($form);
 
        // Field for entering max flags
        $fieldNewMaxFlags = new TextField('newMaxFlags');
        $textareaValidator = new IntValidator(1, 999);
        $fieldNewMaxFlags->addValidator($textareaValidator);
        $form->addElement($fieldNewMaxFlags);

        // Field for entering cron frequency
        $fieldNewCronFrequency = new TextField('newCronFrequency');
        $textareaValidator = new IntValidator(1, 43800);
        $fieldNewCronFrequency->addValidator($textareaValidator);
        $form->addElement($fieldNewCronFrequency);

        //Selectbox field
        $fieldNewSuspendModerator = new Selectbox("newSuspendModerator");
        $fieldNewSuspendModerator->setInvitation($language->text("autosuspend", "select"));
        $fieldNewSuspendModerator->setOptions(array(
            0 => $language->text("autosuspend", "no"),
            1 => $language->text("autosuspend", "yes")
        ));
        $form->addElement($fieldNewSuspendModerator);

        // Field for entering suspend message
        $fieldNewSuspendMessage = new TextField('newSuspendMessage');
        $textareaValidator = new StringValidator(1, 1000);
        $fieldNewSuspendMessage->addValidator($textareaValidator);
        $form->addElement($fieldNewSuspendMessage);

        // Field for entering flag-types
        $fieldFlagTypes = new CheckboxGroup("newFlagTypes");
        $fieldFlagTypes->setOptions(array(
            self::USER_PROFILE => $language->text("autosuspend", "user_profiles_label"),
            self::USER_STATUS => $language->text("autosuspend", "status_updates_label"),
            self::FORUM_TOPIC => $language->text("autosuspend", "forum_posts_label"),
            self::FORUM_POST => $language->text("autosuspend", "forum_replies_label"),
            self::GROUP => $language->text("autosuspend", "groups_label"),
            self::PHOTO => $language->text("autosuspend", "photos_label"),
            self::VIDEO => $language->text("autosuspend", "videos_label"),
            self::COMMENT => $language->text("autosuspend", "comments_label")
        ));
        $fieldFlagTypes->setValue($flagsCurrentlyBeingChecked);
        $fieldFlagTypes->setColumnCount(1);
        $form->addElement($fieldFlagTypes);
 
        // Add submit button
        $submit = new Submit('add');
        $submit->setValue($language->text("autosuspend", "admin_page_update_button"));
        $form->addElement($submit);
 
        // Process form after submit
        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                // Assign new values, or keep old value if field is empty
                $newMaxFlags = $data['newMaxFlags'] ? $data['newMaxFlags'] : $maxFlags;
                $newCronFrequency = $data['newCronFrequency'] ? $data['newCronFrequency'] : $cronFrequency;
                $newSuspendModerator = isset($data['newSuspendModerator']) ? $data['newSuspendModerator'] : $suspendModerator;
                $newSuspendMessage = $data['newSuspendMessage'] ? $data['newSuspendMessage'] : $suspendMessage;
                $newFlagTypes = $data['newFlagTypes'];

                OW::getConfig()->saveConfig('autosuspend', 'max_flags', $newMaxFlags);
                OW::getConfig()->saveConfig('autosuspend', 'cron_frequency', $newCronFrequency);
                OW::getConfig()->saveConfig('autosuspend', 'suspend_mods', $newSuspendModerator);
                OW::getConfig()->saveConfig('autosuspend', 'flags_to_check', json_encode($newFlagTypes));

                $currentLanguageId = BOL_LanguageService::getInstance()->getCurrent()->getId();
                BOL_LanguageService::getInstance()->addOrUpdateValue($currentLanguageId, 'autosuspend', 'suspension_reason', $newSuspendMessage, true);

                OW::getFeedback()->info($language->text("autosuspend", "admin_page_success"));

                $this->redirect();
            }
        }
    }
}