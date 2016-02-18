<?php
/**
 * Copyright 2016 pixeltricks GmbH
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
                SPL.Title LIKE \'%s%%\'',
            $searchTerm,
            implode('%', $searchTermParts)
    );
} else {
    $finalizedSearchTerm = sprintf(
            '
                SPL.Title LIKE \'%s%%\'',
            $searchTerm
    );
}
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
        $productIDs[]  = $assoc['SilvercartProductID'];
        $resultArray[] = array(
            'Title'     => $assoc['Title'],
            'ID'        => $assoc['SilvercartProductID'],
            'Price'     => number_format($assoc['PriceGrossAmount'], 2, ',', '.'),
            'Currency'  => $assoc['PriceGrossCurrency'],
        );
    }
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
     * @since 28.10.2013
     */
    public static function addAdditionalResults(&$resultArray, $searchTerm, $mysqli, $ignoreProductIDs) {
        $searchTermParts    = explode(' ', $searchTerm);
        if (count($searchTermParts) > 1) {
            $finalizedSearchTerm = sprintf(
                    'SPL.Title LIKE \'%%%s%%\' OR
                     SPL.Title LIKE \'%%%s%%\'',
                    $searchTerm,
                    implode('%', $searchTermParts)
            );
        } else {
            $finalizedSearchTerm = sprintf(
                    'SPL.Title LIKE \'%%%s%%\'',
                    $searchTerm
            );
        }
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
                $resultArray[] = array(
                    'Title'     => $assoc['Title'],
                    'ID'        => $assoc['SilvercartProductID'],
                    'Price'     => number_format($assoc['PriceGrossAmount'], 2, ',', '.'),
                    'Currency'  => $assoc['PriceGrossCurrency'],
                );
            }
            $result->close();
        }
    }
    
}