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
 * Controller to direct to the right product detail.
 * 
 * @package Silvercart
 * @subpackage Search
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 17.09.2013
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @copyright 2013 pixeltricks GmbH
 */
class SilvercartSearchAutocompletion_Controller extends Controller {
    
    /**
     * Allowed actions
     *
     * @var array
     */
    public static $allowed_actions = array(
        'gotoresult',
    );
    
    /**
     * Redirects to the product with the given product ID if allowed.
     * 
     * @param SS_HTTPRequest $request Request
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.09.2013
     */
    public function gotoresult(SS_HTTPRequest $request) {
        $ID = $request->param('ID');
        if (is_numeric($ID)) {
            $products = SilvercartProduct::getProducts('"SilvercartProduct"."ID" = \'' . $ID . '\'');
            if ($products instanceof DataList &&
                $products->count() > 0) {
                $product = $products->first();
                $this->redirect($product->Link());
            }
        }
        if (!$this->redirectedTo()) {
            $this->redirectBack();
        }
    }
    
}