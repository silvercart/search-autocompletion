# SilverCart Search Autocompletion

## Verhalten
Die Live-Suche wird ausgelöst, wenn sie im Suchfeld mindestens 3 Zeichen eingeben haben. Dabei sendet das JavaScript eine AJAX-Anfrage über die URL /ssa an den Controller SilverCart\Search\Autocompletion\Control\Controller.

Der Suchprozess besteht aus 3 Schritten:

1. Genaue Suche - es werden Produkte gesucht, deren Titel dem Suchschema **Title LIKE 'searchterm%'** entspricht.
2. wird eine weniger genaue Suche durchgeführt (LIKE '%searchterm%') und es
3. wird eine weniger genaue Suche nach den Suchbegriffen gestartet, wenn sie mehrere Suchbegriffe eingegeben haben

Schritt 2./3. wird nur dann durchgeführt, wenn durch die vorhergehenden Schritte weniger als 20 (Standardwert von SilverCart\Search\Autocompletion\Control\Controller::$results_limit) Ergebnisse erzielt wurden.

### Maximale Anzahl der Suchergebnisse anpassen
Die Maximale Anzahl der Suchergebnisse ist laut Standardeinstellung 20. Um diese Einstellung zu ändern, gibt es zwei Wege:

#### 1. Ändern der Einstellung über die /mysite/_config.php
Im folgenden Beispiel wird die maximale Anzahl der Suchergebnisse über PHP auf 30 erhöht.

	```php
	<?php
	// ...
	use SilverCart\Search\Autocompletion\Control\Controller;
	// set the max results limit to 30
	Controller::config()->update('results_limit', 30);
	```

#### 2. Ändern der Einstellung über die /mysite/_config/config.yml
Im folgenden Beispiel wird die maximale Anzahl der Suchergebnisse über YAML auf 25 erhöht.

	```yaml
	SilverCart\Search\Autocompletion\Control\Controller:
            results_limit: 25
	```

## Beispiel

*Suchbegriffe: Einhorn Regenbogen*

Im ersten Schritt wird die Datenbank direkt nach Produkten abgefragt, die mit den exakten Suchbgegriffen "Einhorn Regenbogen" beginnen.
Wenn weniger als 20 Ergebnisse gefunden werden, wird die Suche auf Produkte erweitert, die die beiden Begriffe "Einhorn Regebogen" in der exakten Reihenfolge irgendwo im Titel haben.

Um optimale Antwortzeiten zu erreichen, wird ein Produkt mit dem Titel "Regenbogen Einhorn" nicht gefunden, wenn Sie nach "Einhorn Regenbogen" suchen.