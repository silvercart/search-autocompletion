# SilverCart Search Autocompletion

## Installation

For optimal performance, the AJAX-callback does not use the SilverStripe framework. 
Instead, the module uses plain PHP and direct database queries. For database access,
the module parses the relevant values from the _ss_environment file. 

Since we also skip SilverCart and all of it's modules, the database name will not be 
known. To solve this, please add the following code to the end of your _ss_environment file:

You can find this code in silvercart/_config.php:

    global $database;
    $database = PIX_CUSTOMER . '_' . PIX_PROJECT;

You also have to add the JavaScript to actually trigger the search either to your Page_Controller or straight into your template:
    
    Requirements::javascript('silvercart_search_autocompletion/js/SilvercartSearchAutocompletion.js');

## Behaviour
After typing into the search field, the JavaScript triggers a request straight to results.php bypassing the SilverStripe Framework to achieve maximum performance.

The search process itself consists of 3 steps:
* strict search, find products matching Title LIKE 'searchterm%'.
In case of less results than configured via SilvercartSearchAutocompletion::$resultsLimit (defaults to 20):
* perform less strict search (LIKE '%searchterm%') and 
* perform a search for single word occurences in case several search terms have been entered

*Example search term: unicorn rainbow*
In the first step, the database is queried for products that start with the exact search term: "unicorn rainbow".
If less than 20 products are being found, products that contain the words "unicorn" and "rainbow" anywhere in the title but in the exact order. 
For best performance, a product titled "rainbow unicorn" will not be found with the search term "unicorn rainbow".

