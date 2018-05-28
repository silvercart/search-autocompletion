# Workaround

`SilverCart\Search\Autocompletion\Model\Pages\PageControllerExtension` is calling `SilverStripe\View\Requirements::themedCSS('client/css/SearchAutocompletion')`.

This call will only work if the source module contains a __*/templates* directory__.

To make sure the call to a themed CSS file within the search-autocompletion module won't result in an error, we added an empty */templates* directory since this module doesn't have any template files.