<?php
/**
 * Copyright 2013 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Silvercart
 * @subpackage Search
 */

/**
 * Adds i18n support to the Javascript.
 * 
 * @package Silvercart
 * @subpackage Search
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 29.11.2013
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @copyright 2013 pixeltricks GmbH
 */
class SilvercartSearchAutocompletionPage_Controller extends DataObjectDecorator {
    
    /**
     * Adds the current locale as JavaScript variable to get the autocompletion
     * i18n context.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function onAfterInit() {
        RequirementsEngine::insertHeadTags('<script type="text/javascript">var SSALOCALE = \'' . i18n::get_locale() . '\';</script>', 'SSALOCALE');
    }
    
}