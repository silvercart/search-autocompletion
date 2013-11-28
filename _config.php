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
 * @subpackage Config
 * 
 * @ignore
 */

RequirementsEngine::registerThemedCssFile('SilvercartSearchAutocompletion', 'silvercart_search_autocompletion');
RequirementsEngine::registerJsFile('silvercart_search_autocompletion/js/SilvercartSearchAutocompletion.js');
RequirementsEngine::insertHeadTags('<script type="text/javascript">var SSALOCALE = \'' . i18n::get_locale() . '\';</script>', 'SSALOCALE');

Director::addRules(
        100,
        array(
            'ssa/$Action/$ID' => 'SilvercartSearchAutocompletion_Controller',
        )
);