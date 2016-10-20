# SilverCart Search Autocompletion

## Installation

Die AJAX-Aufrufes des Live-Suche-Moduls umgehen das SilverStripe-Framework vollständig um optimale Suchgeschwindigkeiten zu erreichen. 
Anstelle des SilverStripe-Frameworks benutzt das Modul einfaches PHP und direkte Datanbankabfragen (ohne ORM). 

Um die Verbindung zur Datenbank herstellen zu können, liest das Modul die notwendigen Zugangsdaten aus der _ss_environment-Datei aus.

Da auch SilverCart und alle anderen Module umgangen werden, ist der Name der Datenbank zur Laufzeit nicht bekannt.
Um das zu lösen, müssen Sie den folgenden Code an das Ende ihrer _ss_environment-Datei hinzufügen:

    global $database;
    $database = PIX_CUSTOMER . '_' . PIX_PROJECT;

Sie finden diesen Code auch in der Datei silvercart/_config.php

Ausserdem müssen Sie in ihrem Page_Controller (oder im Template) das JavaScript hinzufügen, dass die Live-Suche auslöst:
    
    Requirements::javascript('silvercart_search_autocompletion/js/SilvercartSearchAutocompletion.js');

## Verhalten
Die Live-Suche wird ausgelöst, wenn sie im Suchfeld mindestens 3 Zeichen eingeben haben. Dabei sendet das JavaScript eine AJAX-Anfrage direkt auf die Datei results.php. Dabei wird das SilverStripe-Framework vollständig umgangen.

Der Suchprozess besteht aus 3 Schritten:
* Genaue Suche - es werden Produkte gesucht, deren Titel dem Suchschema **Title LIKE 'searchterm%'** entspricht.
Für den Fall, dass weniger Ergebnisse zurückgeliefert werden als über SilvercartSearchAutocompletion::$resultsLimit (Standardwert:20) definiert sind:
* wird eine weniger genaue Suche durchgeführt (LIKE '%searchterm%') und es
* wird eine weniger genaue Suche nach den Suchbegriffen gestartet, wenn sie mehrere Suchbegriffe eingegeben haben

## Beispiel

*Suchbegriffe: Einhorn Regenbogen*
Im ersten Schritt wird die Datenbank direkt nach Produkten abgefragt, die mit den exakten Suchbgegriffen "Einhorn Regenbogen" beginnen.
Wenn weniger als 20 Ergebnisse gefunden werden, wird die Suche auf Produkte erweitert, die die beiden Begriffe "Einhorn Regebogen" in der exakten Reihenfolge irgendwo im Titel haben.

Um optimale Antwortzeiten zu erreichen, wird ein Produkt mit dem Titel "Regenbogen Einhorn" nicht gefunden, wenn Sie nach "Einhorn Regenbogen" suchen.

