# SilverCart Search Autocompletion

Installation

For optimal performance, the AJAX-callback does not use the SilverStripe framework. 
Instead, the module uses plain PHP and direct database queries. For database access,
the module parses the relevant values from the _ss_environment file. 

Since we also skip SilverCart and all of it's modules, the database name will not be 
known. To solve this, please add the following code to the end of your _ss_environment file:

global $database;
$database = PIX_CUSTOMER . '_' . PIX_PROJECT;

You can find this code in silvercart/_config.php
