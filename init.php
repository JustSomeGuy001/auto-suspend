<?php

// Add route for admin page
OW::getRouter()->addRoute(new OW_Route('autosuspend.admin', 'admin/plugins/autosuspend', 'AUTOSUSPEND_CTRL_Admin', 'admin'));
