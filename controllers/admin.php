<?php

class AUTOSUSPEND_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function admin()
    {

        $language = OW::getLanguage();

        // Set page title and heading
        $this->setPageTitle($language->text("autosuspend", "admin_page_title"));
        $this->setPageHeading($language->text("autosuspend", "admin_page_heading"));

        // Assign variables
        $maxFlags = OW::getConfig()->getValue('autosuspend', 'max_flags');
        $cronFrequency = OW::getConfig()->getValue('autosuspend', 'cron_frequency');
        $suspendMessage = OW::getLanguage()->text("autosuspend", "suspension_reason");

        // Assignments for use in HTML page
        $this->assign('maxFlags', $maxFlags);
        $this->assign('cronFrequency', $cronFrequency);
        $this->assign('suspendMessage', $suspendMessage);
 
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

        // Field for entering suspend message
        $fieldNewSuspendMessage = new TextField('newSuspendMessage');
        $textareaValidator = new StringValidator(1, 1000);
        $fieldNewSuspendMessage->addValidator($textareaValidator);
        $form->addElement($fieldNewSuspendMessage);
 
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

                $newMaxFlags = $data['newMaxFlags'] ? $data['newMaxFlags'] : $maxFlags;
                $newCronFrequency = $data['newCronFrequency'] ? $data['newCronFrequency'] : $cronFrequency;
                $newSuspendMessage = $data['newSuspendMessage'] ? $data['newSuspendMessage'] : $suspendMessage;

                OW::getConfig()->saveConfig('autosuspend', 'max_flags', $newMaxFlags);
                OW::getConfig()->saveConfig('autosuspend', 'cron_frequency', $newCronFrequency);

                $currentLanguageId = $languageService->getCurrent()->getId();
                BOL_LanguageService::getInstance()->addOrUpdateValue($currentLanguageId, 'autosuspend', 'suspension_reason', $newSuspendMessage, true);

                OW::getFeedback()->info($language->text("autosuspend", "admin_page_success"));

                $this->redirect();
            }
        }
    }
}