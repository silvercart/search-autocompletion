<?php

namespace SilverCart\Search\Autocompletion\Control;

use SilverCart\Model\Product\Product;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * Controller to direct to the right product detail.
 * 
 * @package SilverCart
 * @subpackage Search_Autocompletion_Controller
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 17.05.2018
 * @license see license file in modules root directory
 * @copyright 2018 pixeltricks GmbH
 */
class Controller extends BaseController {
    
    /**
     * Allowed actions
     *
     * @var array
     */
    private static $allowed_actions = [
        'getresults',
        'gotoresult',
    ];
    
    /**
     * Maximum count of results to show.
     *
     * @var int
     */
    private static $results_limit = 20;


    /**
     * Returns the search results.
     * 
     * @param SS_HTTPRequest $request Request
     * 
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.05.2018
     */
    public function getresults(HTTPRequest $request) {
        $jsonResult = json_encode([]);
        $searchTerm = $request->postVar('searchTerm');
        if (is_null($searchTerm)) {
            $searchTerm = $request->getVar('searchTerm');
        }
        if (!is_null($searchTerm)) {
            $filter  = $this->getWhereClause($searchTerm);
            $limit   = $this->config()->get('results_limit');
            $results = Product::get()->where($filter)->limit($limit);
            if ($results->count() < $limit) {
                $additionalResults = $this->getAdditionalResults($searchTerm, $limit - $results->count());
                if ($additionalResults->exists()) {
                    $results = new ArrayList($results->toArray());
                    $results->merge($additionalResults);
                }
            }
            $jsonResult = $this->getJsonResult($results);
        }
        return $jsonResult;
    }
    
    /**
     * Redirects to the product with the given product ID if allowed.
     * 
     * @param SS_HTTPRequest $request Request
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.05.2018
     */
    public function gotoresult(HTTPRequest $request) {
        $ID = $request->param('ID');
        if (is_numeric($ID)) {
            $products = Product::get()->byID($ID);
            if ($products instanceof DataList &&
                $products->exists()) {
                $product = $products->first();
                $this->redirect($product->Link());
            }
        }
        if (!$this->redirectedTo()) {
            $this->redirectBack();
        }
    }
    
    /**
     * Returns additional search results with a less precise filter to fill the 
     * result list to max $limit results.
     * 
     * @param string $searchTerm Search term
     * @param int    $limit      Result limit
     * 
     * @return DataList
     */
    protected function getAdditionalResults($searchTerm, $limit) {
        $filter            = $this->getWhereClause($searchTerm, '%%');
        $additionalResults = Product::get()->where($filter)->limit($limit);
        $this->extend('updateAdditionalResults', $additionalResults, $searchTerm, $limit);
        return $additionalResults;
    }
    
    /**
     * Returns the SQL where clause to filter with.
     * 
     * @param string $searchTerm Search term
     * @param string $likePrefix Prefix for the LIKE filter (e.g. %%)
     * 
     * @return string
     */
    protected function getWhereClause($searchTerm, $likePrefix = '') {
        $searchTermParts = explode(' ', $searchTerm);
        if (count($searchTermParts) > 1) {
            $whereClause = sprintf("
                        SilvercartProductTranslation.Title LIKE '" . $likePrefix . "%s%%' OR
                        SilvercartProductTranslation.Title LIKE '" . $likePrefix . "%s%%' OR
                        SilvercartProduct.ProductNumberShop LIKE '" . $likePrefix . "%s%%' OR
                        SilvercartProduct.ProductNumberShop LIKE '" . $likePrefix . "%s%%'",
                        $searchTerm,
                        implode('%', $searchTermParts),
                        $searchTerm,
                        implode('%', $searchTermParts)
            );
        } else {
            $whereClause = sprintf("
                        SilvercartProductTranslation.Title LIKE '" . $likePrefix . "%s%%' OR
                        SilvercartProduct.ProductNumberShop LIKE '" . $likePrefix . "%s%%'",
                        $searchTerm,
                        $searchTerm
            );
        }
        $this->extend('updateWhereClause', $whereClause, $searchTerm);
        return $whereClause;
    }
    
    /**
     * Builds and returns the JSON result.
     * 
     * @param DataList $products Products
     * 
     * @return string
     */
    protected function getJsonResult($products) {
        $arrayData = [];
        foreach ($products as $product) {
            /* @var $product Product */
            $singleArrayData = [
                'ProductNumberShop' => $product->ProductNumberShop,
                'Title'             => $product->Title,
                'ID'                => $product->ID,
                'Price'             => number_format($product->getPrice()->getAmount(), 2, ',', '.'),
                'Currency'          => $product->getPrice()->getCurrency(),
                'PriceNice'         => $product->getPriceNice(),
            ];
            $this->extend('updateSingleJsonResult', $singleArrayData, $product);
            $arrayData[] = $singleArrayData;
        }
        $this->extend('updateJsonResult', $arrayData, $products);
        return json_encode($arrayData);
    }
    
}