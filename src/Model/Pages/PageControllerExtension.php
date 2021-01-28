<?php

namespace SilverCart\Search\Autocompletion\Model\Pages;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

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
class PageControllerExtension extends Extension
{
    /**
     * Adds some JS files.
     * 
     * @param array &$jsFiles JS files
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.07.2018
     */
    public function updateRequireExtendedJavaScript(array &$jsFiles) : void
    {
        $jsFiles = array_merge(
            $jsFiles,
            [
                ThemeResourceLoader::inst()->findThemedJavascript('client/javascript/SearchAutocompletion', SSViewer::get_themes()),
            ]
        );
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
    public function onAfterInit() : void
    {
        Requirements::themedCSS('client/css/SearchAutocompletion');
    }
}