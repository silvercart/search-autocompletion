# SilverCart Search Autocompletion

## Behavior
After typing into the search field (at least 3 characters), the JavaScript triggers a request straight to the controller SilverCart\Search\Autocompletion\Control\Controller by calling the URL /ssa.

The search process itself consists of 3 steps:

1. strict search, find products matching Title LIKE 'searchterm%'.
2. perform less strict search (LIKE '%searchterm%') and 
3. perform a search for single word occurences in case several search terms have been entered

Step 2./3. is only processed if the previous step(s) resulted in less then 20 (default value of SilverCart\Search\Autocompletion\Control\Controller::$results_limit) search results.

### Adjust maximum amount of search results
THe maximum amount of search results is 20 by default. To change this setting, there are two ways:

#### 1. Change the setting through the /mysite/_config.php
The following example shows how to increase the maximum amount of search results to 30 by using PHP.

	```php
	<?php
	// ...
	use SilverCart\Search\Autocompletion\Control\Controller;
	// set the max results limit to 30
	Controller::config()->update('results_limit', 30);
	```

#### 2. Change the setting through the /mysite/_config/config.yml
The following example shows how to increase the maximum amount of search results to 25 by using YAML.

	```yaml
	SilverCart\Search\Autocompletion\Control\Controller:
            results_limit: 25
	```

## Example

*Example search term: unicorn rainbow*

In the first step, the database is queried for products that start with the exact search term: "unicorn rainbow".
If less than 20 products are being found, products that contain the words "unicorn" and "rainbow" anywhere in the title but in the exact order. 

For best performance, a product titled "rainbow unicorn" will not be found with the search term "unicorn rainbow".