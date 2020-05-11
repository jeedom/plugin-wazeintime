Warte rechtzeitig 
============

Beschreibung 
-----------

Mit diesem Plugin können Sie die Reiseinformationen (Verkehr berücksichtigt) über
Waze. Dieses Plugin funktioniert möglicherweise nicht mehr, wenn Waze dies nicht mehr akzeptiert
fragt seine Seite ab

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

Konfiguration 
-------------

### Plugin Konfiguration: 

a. Installation / Erstellung

Um das Plugin nutzen zu können, müssen Sie herunterladen und installieren
aktiviere es wie jedes Jeedom Plugin.

Danach müssen Sie Ihre Reise (n) erstellen :

Gehen Sie zum Plugins / Organisationsmenü, dort finden Sie die
Waze Duration Plugin :

![configuration1](../images/configuration1.jpg)

Dann gelangen Sie auf die Seite, auf der Ihre Ausrüstung (Sie) aufgelistet ist
kann mehrere Routen haben) und die Sie erstellen können

![wazeintime screenshot2](../images/wazeintime_screenshot2.jpg)

Klicken Sie auf die Schaltfläche Reise hinzufügen oder auf die Schaltfläche + :

![config2](../images/config2.jpg)

Sie gelangen dann auf die Konfigurationsseite Ihrer Reise:

![wazeintime screenshot3](../images/wazeintime_screenshot3.jpg)

Auf dieser Seite finden Sie drei Abschnitte :

ich. Allgemein

In diesem Abschnitt finden Sie alle Jeedom-Konfigurationen. A
Kennen Sie den Namen Ihrer Ausrüstung, das gewünschte Objekt
Ordnen Sie es der Kategorie zu, wenn das Gerät aktiv sein soll oder
Nein, und schließlich, wenn Sie möchten, dass es im Dashboard angezeigt wird.

ich. Konfiguration

Dieser Abschnitt ist einer der wichtigsten, mit dem Sie die anpassen können
Start- und Endpunkt :

-   Diese Informationen müssen die Breiten- und Längengrade der Positionen sein

-   Sie können über die Website in gefunden werden
    Klicken Sie auf den Link der Seite (Sie müssen nur eine eingeben
    Adresse und klicken Sie auf GPS-Koordinaten abrufen)

    ich. Systemsteuerung

![config3](../images/config3.jpg)

-   Dauer 1 : Reisedauer 1

-   Dauer 2 : Reisezeit mit der Alternativroute

-   Route 1 : Route 1

-   Route 2 : Alternative Route

-   Dauer zurück 1 : Rückgabezeit mit Fahrt 1

-   Dauer zurück 2 : Rückgabezeit mit der alternativen Route

-   Route zurück 1 : Route zurück 1

-   Route zurück 2 : Alternative Rückreise

-   Aktualisieren : Informationen aktualisieren

Alle diese Befehle sind über Szenarien und über das Dashboard verfügbar

### Das Widget : 

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

-   Die Schaltfläche oben rechts aktualisiert die Informationen.

-   Alle Informationen sind sichtbar (für Reisen, wenn die Reise ist
    lang kann es abgeschnitten werden, aber die Vollversion ist in sichtbar
    Maus über lassen)

### Wie werden die Nachrichten aktualisiert? : 

Die Informationen werden alle 30 Minuten aktualisiert. Du kannst
Aktualisieren Sie sie bei Bedarf über ein Szenario mit dem Befehl refresh oder
über den Bindestrich mit den Doppelpfeilen

Änderungsprotokoll 
=========

Changelog detailliert :
<https://github.com/jeedom/plugin-wazeintime/commits/stable>
