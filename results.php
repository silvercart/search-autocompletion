<?php
/**
 * Copyright 2018 pixeltricks GmbH
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

if (!array_key_exists('searchTerm', $_POST)) {
    exit();
}

/**
 * Include _ss_environment.php files
 */
$envFiles = array(
    '_ss_environment.php',
    '../_ss_environment.php',
    '../../_ss_environment.php',
    '../../../_ss_environment.php'
);
foreach ($envFiles as $envFile) {
    if (@file_exists($envFile)) {
        define('SS_ENVIRONMENT_FILE', $envFile);
        include_once($envFile);
        break;
    }
}

if (!defined('SS_ENVIRONMENT_FILE')) {
    user_error("_ss_environment.php is missing.", E_WARNING);
    exit();
}

if (!defined('SS_DATABASE_USERNAME') ||
   !defined('SS_DATABASE_PASSWORD')) {
    user_error("SS_DATABASE_USERNAME and/or SS_DATABASE_PASSWORD is not defined.", E_WARNING);
    exit();
}
if (array_key_exists('locale', $_GET) &&
    !empty($_GET['locale'])) {
    SilvercartSearchAutocompletion::$locale = $_GET['locale'];
}
if (array_key_exists('pt', $_GET) &&
    !empty($_GET['pt'])) {
    if ($_GET['pt'] == 1) {
        SilvercartSearchAutocompletion::$priceField    = 'PriceNetAmount';
        SilvercartSearchAutocompletion::$currencyField = 'PriceNetCurrency';
    }
}

global $database;
$databaseConfig = array(
    "type"      => defined('SS_DATABASE_CLASS')     ? SS_DATABASE_CLASS     : "MySQLDatabase",
    "server"    => defined('SS_DATABASE_SERVER')    ? SS_DATABASE_SERVER    : 'localhost', 
    "username"  => SS_DATABASE_USERNAME, 
    "password"  => SS_DATABASE_PASSWORD, 
    "database"  => (defined('SS_DATABASE_PREFIX')   ? SS_DATABASE_PREFIX    : '') . $database . (defined('SS_DATABASE_SUFFIX') ? SS_DATABASE_SUFFIX : ''),
);

$mysqli = new mysqli(
        $databaseConfig['server'],
        $databaseConfig['username'],
        $databaseConfig['password'],
        $databaseConfig['database']
);

if ($mysqli->connect_errno) {
    user_error(sprintf("Connect failed: %s", $mysqli->connect_error), E_USER_WARNING);
    exit();
}

$jsonResult         = '';
$searchTerm         = addslashes($_POST['searchTerm']);
$searchTermParts    = explode(' ', $searchTerm);
if (count($searchTermParts) > 1) {
    $finalizedSearchTerm = sprintf(
            '
                SPL.Title LIKE \'%s%%\' OR
                SPL.Title LIKE \'%s%%\' OR
                SP.ProductNumberShop LIKE \'%s%%\' OR
                SP.ProductNumberShop LIKE \'%s%%\'',
                $searchTerm,
                implode('%', $searchTermParts),
                $searchTerm,
                implode('%', $searchTermParts)
    );
} else {
    $finalizedSearchTerm = sprintf(
            '
                SPL.Title LIKE \'%s%%\' OR
                SP.ProductNumberShop LIKE \'%s%%\'',
                $searchTerm,
                $searchTerm
    );
}
SilvercartSearchAutocompletion::extend('updateFinalizedSearchTerm', $finalizedSearchTerm, $searchTerm);
$searchQuery = sprintf(
        'SELECT * FROM SilvercartProduct AS SP LEFT JOIN SilvercartProductLanguage AS SPL ON (SP.ID = SPL.SilvercartProductID) WHERE 
            isActive = 1 AND
            SilvercartProductGroupID != 0 AND 
            (
                %s
            )
            AND Locale = \'%s\'
        LIMIT 0, %s',
        $finalizedSearchTerm,
        SilvercartSearchAutocompletion::$locale,
        SilvercartSearchAutocompletion::$resultsLimit
);

/* Request correct charset */
$mysqli->query('SET NAMES utf8');

/* @var $result mysqli_result */
$result = $mysqli->query($searchQuery);
if ($result) {
    $resultArray = array();
    $productIDs  = array();
    while ($assoc = $result->fetch_assoc()) {
        $addToTitle = ' ';
        SilvercartSearchAutocompletion::extend('addToTitle', $assoc, $addToTitle, $searchTerm, SilvercartSearchAutocompletion::$locale, $mysqli);
        $title = $assoc['Title'] . $addToTitle;
        SilvercartSearchAutocompletion::extend('updateTitle', $title, $assoc, $searchTerm, SilvercartSearchAutocompletion::$locale, $mysqli);
        $productIDs[]  = $assoc['SilvercartProductID'];
        $resultArray[] = array(
            'ProductNumberShop' => $assoc['ProductNumberShop'],
            'Title'             => $title,
            'ID'                => $assoc['SilvercartProductID'],
            'Price'             => number_format($assoc[SilvercartSearchAutocompletion::$priceField], 2, ',', '.'),
            'Currency'          => SilvercartSearchAutocompletion::nice_currency($assoc[SilvercartSearchAutocompletion::$currencyField]),
            'PriceNice'         => SilvercartSearchAutocompletion::nice_money($assoc[SilvercartSearchAutocompletion::$priceField], $assoc[SilvercartSearchAutocompletion::$currencyField]),
        );
    }
    SilvercartSearchAutocompletion::extend('updateResults', $resultArray, $assoc);
    $result->close();
    
    /* if there is room for additional search results, try to find more results with a less strict query  */
    if (count($resultArray) < SilvercartSearchAutocompletion::$resultsLimit) {
        SilvercartSearchAutocompletion::addAdditionalResults($resultArray, $searchTerm, $mysqli, $productIDs);
    }
    $jsonResult = json_encode($resultArray); 
}

$mysqli->close();

print $jsonResult;
exit();

/**
 * SilvercartSearchAutocompletion
 * 
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2013 pixeltricks GmbH
 * @since 28.10.2013
 * @license none
 */
class SilvercartSearchAutocompletion {
    
    /**
     * Results limit
     *
     * @var int
     */
    public static $resultsLimit = 20;
    
    /**
     * Locale
     *
     * @var string
     */
    public static $locale = 'de_DE';
    
    /**
     * Price field
     *
     * @var string
     */
    public static $priceField = 'PriceGrossAmount';
    
    /**
     * currency field
     *
     * @var string
     */
    public static $currencyField = 'PriceGrossCurrency';

    /**
     * Adds additional results from a less strict search to $resultArray
     * 
     * @param array  &$resultArray     Results to extend
     * @param string $searchTerm       Search term
     * @param mysqli $mysqli           MySQL connection
     * @param array  $ignoreProductIDs List of product IDs to ignore
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.01.2018
     */
    public static function addAdditionalResults(&$resultArray, $searchTerm, $mysqli, $ignoreProductIDs) {
        $searchTermParts    = explode(' ', $searchTerm);
        if (count($searchTermParts) > 1) {
            $finalizedSearchTerm = sprintf(
                    '
                        SPL.Title LIKE \'%%%s%%\' OR
                        SPL.Title LIKE \'%%%s%%\' OR
                        SP.ProductNumberShop LIKE \'%%%s%%\' OR
                        SP.ProductNumberShop LIKE \'%%%s%%\'',
                        $searchTerm,
                        implode('%', $searchTermParts),
                        $searchTerm,
                        implode('%', $searchTermParts)
            );
        } else {
            $finalizedSearchTerm = sprintf(
                    'SPL.Title LIKE \'%%%s%%\' OR
                     SP.ProductNumberShop LIKE \'%%%s%%\'',
                    $searchTerm,
                    $searchTerm
            );
        }
        self::extend('updateAdditionalFinalizedSearchTerm', $finalizedSearchTerm, $searchTerm);
        $ignoreProductIDsTerm = '';
        if (count($ignoreProductIDs) > 0) {
            $ignoreProductIDsTerm = ' AND SP.ID NOT IN (' . implode(',', $ignoreProductIDs) . ')';
        }
        $searchQuery = sprintf(
                'SELECT * FROM SilvercartProduct AS SP LEFT JOIN SilvercartProductLanguage AS SPL ON (SP.ID = SPL.SilvercartProductID) WHERE 
                    isActive = 1 AND
                    SilvercartProductGroupID != 0 AND 
                    (
                        %s
                    )%s
                AND Locale = \'%s\'
                LIMIT 0, %s',
                $finalizedSearchTerm,
                $ignoreProductIDsTerm,
                self::$locale,
                self::$resultsLimit - count($resultArray)
        );
        
        /* @var $result mysqli_result */
        $result = $mysqli->query($searchQuery);
        if ($result) {
            while ($assoc = $result->fetch_assoc()) {
                $addToTitle = ' ';
                self::extend('addToTitle', $assoc, $addToTitle, $searchTerm, SilvercartSearchAutocompletion::$locale, $mysqli);
                $title = $assoc['Title'] . $addToTitle;
                self::extend('updateTitle', $title, $assoc, $searchTerm, SilvercartSearchAutocompletion::$locale, $mysqli);
                $resultArray[] = array(
                    'ProductNumberShop' => $assoc['ProductNumberShop'],
                    'Title'             => $title,
                    'ID'                => $assoc['SilvercartProductID'],
                    'Price'             => number_format($assoc[SilvercartSearchAutocompletion::$priceField], 2, ',', '.'),
                    'Currency'          => SilvercartSearchAutocompletion::nice_currency($assoc[SilvercartSearchAutocompletion::$currencyField]),
                    'PriceNice'         => SilvercartSearchAutocompletion::nice_money($assoc[SilvercartSearchAutocompletion::$priceField], $assoc[SilvercartSearchAutocompletion::$currencyField]),
                );
                SilvercartSearchAutocompletion::extend('updateResults', $resultArray, $assoc);
            }
            $result->close();
        }
        
        self::extend('addSearchResults', $searchTerm, SilvercartSearchAutocompletion::$locale, $mysqli, $resultArray);
    }
    
    /**
     * Executes an extension hook.
     * 
     * @param string $method Extension method to call
     * @param mixed  &$a1    Extension parameter 1
     * @param mixed  &$a2    Extension parameter 2
     * @param mixed  &$a3    Extension parameter 3
     * @param mixed  &$a4    Extension parameter 4
     * @param mixed  &$a5    Extension parameter 5
     * @param mixed  &$a6    Extension parameter 6
     * @param mixed  &$a7    Extension parameter 7
     * 
     * @return void
     * 
     * @global array $searchAutoCompletionExtensions
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 16.12.2016
     */
    public static function extend($method, &$a1=null, &$a2=null, &$a3=null, &$a4=null, &$a5=null, &$a6=null, &$a7=null) {
        global $searchAutoCompletionExtensions;
        if (!is_array($searchAutoCompletionExtensions)) {
            $searchAutoCompletionExtensions = [];
        }
        foreach ($searchAutoCompletionExtensions as $path => $classname) {
            require_once '../' . $path;
            $extension = new $classname();
            if (method_exists($extension, $method)) {
                $extension->$method($a1, $a2, $a3, $a4, $a5, $a6, $a7);
            }
        }
    }
    
    /**
     * Adds the given extension.
     * 
     * @param string $extension Extension
     * @param string $path      Extension file path (relative)
     * 
     * @return void
     *
     * @global array $searchAutoCompletionExtensions
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 28.02.2018
     */
    public static function add_extension($extension, $path) {
        global $searchAutoCompletionExtensions;
        if (!is_array($searchAutoCompletionExtensions)) {
            $searchAutoCompletionExtensions = [];
        }
        if (!array_key_exists($path, $searchAutoCompletionExtensions)) {
            $searchAutoCompletionExtensions[$path] = $extension;
        }
    }

    /**
     * Returns a money (price) string in a nice format dependant on the current locale.
     * 
     * @param float  $amount   Amount
     * @param string $currency Currency string
     * @param array  $options  Additional options
     * 
	 * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 16.12.2016
	 */
	public static function nice_money($amount, $currency, $options = array()) {
        $includePath = get_include_path();
        $includePath = $includePath . PATH_SEPARATOR . str_replace('silvercart_search_autocompletion', '', getcwd()) . 'framework/thirdparty/';
        set_include_path($includePath);
        require_once 'Zend/Currency.php';
		$currencyLib = new Zend_Currency(null, SilvercartSearchAutocompletion::$locale);
        if (!isset($options['display'])) {
            $options['display'] = Zend_Currency::USE_SYMBOL;
        }
		if (!isset($options['currency'])) {
            $options['currency'] = $currency;
        }
		if (!isset($options['symbol'])) {
			$options['symbol'] = $currencyLib->getSymbol($options['currency'], SilvercartSearchAutocompletion::$locale);
		}
		return (is_numeric($amount)) ? $currencyLib->toCurrency($amount, $options) : '';
	}
    
	/**
     * Returns a money (price) string in a nice format dependant on the current locale.
     * 
     * @param string $currency Currency string
     * 
	 * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 16.12.2016
	 */
	public static function nice_currency($currency) {
        $includePath = get_include_path();
        $includePath = $includePath . PATH_SEPARATOR . str_replace('silvercart_search_autocompletion', '', getcwd()) . 'framework/thirdparty/';
        set_include_path($includePath);
        require_once 'Zend/Currency.php';
		$currencyLib = new Zend_Currency(null, SilvercartSearchAutocompletion::$locale);
		return $currencyLib->getSymbol($currency, SilvercartSearchAutocompletion::$locale);
	}
    
}