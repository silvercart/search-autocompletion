<?php

namespace SilverCart\Search\Autocompletion\Model\Pages;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Adds i18n support to the Javascript.
 * 
 * @package SilverCart
 * @subpackage Search_Autocompletion_Model_Pages
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 17.05.2018
 * @license see license file in modules root directory
 * @copyright 2018 pixeltricks GmbH
 */
class PageControllerExtension extends Extension {
    
    /**
     * Updates the default JS files.
     * 
     * @param array &$jsFiles JS files
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.05.2018
     */
    public function updatedJSRequirements(&$jsFiles) {
        $jsFiles[] = 'silvercart/search-autocompletion:client/javascript/SearchAutocompletion.js';
    }
    
    /**
     * Adds the current locale as JavaScript variable to get the autocompletion
     * i18n context.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.05.2018
     */
    public function onAfterInit() {
        Requirements::themedCSS('client/css/SearchAutocompletion');
    }
    
}