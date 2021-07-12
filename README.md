# Font Awesome Icon Picker🐥

![Screenshot](https://github.com/FriendsOfREDAXO/fa_iconpicker/blob/assets/icon_screen.png?raw=true)


Der Iconpicker wird an Text-Eingabefelder angebunden. Beim Klick öffnet sich ein **Picker Widget**. Anschließend werden die Icons des **aktuell eingestellten
 Pakets** dynamisch nachgeladen. Über die Einstellungen zu Zeilen und Spalten im Picker Widget kann der Picker beim Scrollen kalkulieren, welche Seite vom
  Server nachgeladen werden soll. Dies beugt der Überlastung des Browsers vor und sichert so eine flüssige Navigation.

Nutzer mit entsprechender Berechtigung können im Verwaltungsbereich des AddOns **mehrere Font Awesome  Pakete hochladen** und unter diesen das für die
 Ausgabe präferierte Paket als **Standard-Paket** auswählen.

Ausgeliefert wird das AddOn mit einer aktuellen Version des freien Paketes. Liegt eine entsprechende Lizenz für ein Pro-Paket vor, kann dieses hochgeladen
 und genutzt werden.

> **ACHTUNG**: Font Awesome bietet auch Desktop-Pakete zum Download an. Sollten diese hochgeladen werden, erfolgt keine Einbindung in den Paket-Pool. Es
> erscheint eine entsprechende Fehlermeldung. 

Das Picker Widget wird am Ende des `<body>`-Tags eingebunden und beim Öffnen absolut unter das zugehörige Eingabefeld positioniert. Damit ist sichergestellt
, dass besondere Layouts (flex, grid etc.) durch das Picker Widget nicht beeinflusst werden.

Die Schrift-Ressourcen für das aktuelle Paket werden auch im Backend geladen. Für die Einbidnung im Frontend siehe unten.

## Features

* Paket-Manager
  * wird mit aktuellem Free-Paket ausgeliefert
  * Support für Pro- und Free-Pakete
  * Support für Subsets (siehe https://fontawesome.com/how-to-use/on-the-desktop/other-topics/subsetter) mit spezieller Markierung (blaues "S" in Paketübersicht)
* komplexer Picker mit zugehöriger Setup-Seite und vielseitigen Anpassungsmöglichkeiten [siehe "Anpassungen"](#Anpassungen)
* am Ziel-Feld (input type=text oder textarea) initialisierbar mit von den Standardeinstellungen abweichenden Settings
* Picker-Features (alles einstellbar):
  * Suche nach `Name`, `Label`, `Code`, `Search Terms`
  * umschaltbare Vorschau-Stile
  * Mehrfachauswahl
  * Ergänzung der Stil-Klasse (`fat`,`fal`,`far`,`fas`,`fad`,`fab`)
  * verschiebbar
  * Wahl des Rückgabewerts (`Name`, `Code`, `ID`, `Label`, `SVG-Code`)
  * Event-Handler
  * Daten-Anfragen via REX API Klasse
* MBlock-Support

-----

## Einbindung Frontend 

Für die Ausgabe eines `<link>`-Tags für das Font Awesome CSS muss folgender Code im `<head>` Bereich des Templates eingefügt werden. **ACHTUNG:** Funktioniert ur direkt im Template, nicht bei includes!
```
REX_FA_ICONPICKER[]
```

-----

## Nutzung des Pickers im Backend

Der Picker wird über die Angabe der Klasse `rex-fa-iconpicker` in Text-Eingabefeldern initialisiert.
 **Moduleingaben**.

```html
<input type="text" class="rex-fa-iconpicker" />
```

----

Alternativ kann man eine Initialisierung mit JavaScript wie folgt vornehmen (im Beispiel mit speziellen Settings für diese Instanz):

```javascript
let fap = new FAPicker(
    document.getElementById('<ID_OF_INPUT_OR_TEXTAREA>'),
    {
        'multiple': true,
        'add-weight': false
    }
);
```

----

Man kann nach der Initialisierung auf die Picker-Instanz zugreifen:

```javascript
let fap = $("#<ID_OF_INPUT_OR_TEXTAREA>").get(0).FAPicker;
```

----

Standardmäßig wird der Picker geöffnet, sobald man in diesen klickt und schließt, sobald man ein Icon auswählt oder den Schließen-Button `X` oben rechts im
 Popup klickt. 

### Anpassungen

Funktionen und Aussehen des Pickers können über Attribute für besondere Anwendungsfälle angepasst werden. Viele Einstellungen können, entsprechende
 Berechtigung vorausgesetzt, generell angepasst werden.

Die Einstellungen werden über HTML-Attribute mit dem Schema `data-fa-X="VALUE"` direkt am Eingabefeld vorgenommen. Ein Beispiel für 6 Icon-Spalten im Picker
-Popup:

```html
<input type="text" class="rex-fa-iconpicker" data-fa-columns="6" />
```

#### Alle Einstellungen im Überblick:

* `rows` - Zeilen im Picker Widget
* `columns` - Spalten im Picker Widget
* `offset` - Seiten im Picker Widget die beim Nachladen zusätzlich geladen und gerendert werden (Bsp.: ein Offset von 1 bei 4 Spalten und 5 Zeilen
 bedeutet, dass nicht nur 20 Icons (1 Seite, 4 * 5) nachgeladen werden, sondern 60 - 1 Seite davor und 1 danach)
* `max-pages` - Performance-Einstellung; legt fest, wieviele Seiten maximal in einem Picker Widget geladen sein dürfen
* `weight-selector` - zeigt Stil-Auswahl am Rand des Picker Widgets an [0, 1]
* `movable` - macht das Picker Widget verschiebbar, Doppelklick auf den Move-Button zum Zurücksetzen [0, 1]
* `details-on-hover` - Bei Mouseover über ein Icon wird ein komplexer Tooltip mit mehr Infos gezeigt und das Icon in den erlaubten Schriftschnitten (siehe `weights`) abgebildet [0, 1]
* `close-with-button` - Schließen wird nur explizit über einen Close Button oben rechts im Picker Widget durchgeführt [0, 1]
* `multiple` - erlaubt Mehrfach-Auswahl [0, 1]
* `weights` - Verfügbare Schriftschnitte als zusammenhängender String (l = Light, r = Regular, s = Solid, d = Duotone, b = Brand), Bsp. `ldb` blendet die Vorschauen für Light, Duotone und Brand Icons ein und erlaubt auch deren Auswahl, wenn `add-weight` = 1 ist
* `add-weight` - fügt bei Auswahl eines Icons nicht nur den Namen des Icons ein (bspw. `fa-user`) sondern auch den aktuell eingestellten
 Schriftschnitt (Beispiel-Ergebnis: `fas fa-user` wenn aktuell der `solid`-Schriftschnitt ausgewählt war) [0, 1]
* `preview-weight` - fügt bei Auswahl eines Icons nicht nur den Namen des Icons ein (bspw. `fa-user`) sondern auch den aktuell eingestellten
   Schriftschnitt (Beispiel-Ergebnis: `fas fa-user` wenn aktuell der `solid`-Schriftschnitt ausgewählt war) [0, 1]
* `insert-value` - Wert, der bei Auswahl des Icons ins Ziel-Feld eingetragen werden soll (Standard: `name`) [`name`, `label`, `icon`, `svg`]
* `sort-by` - legt die Sortierung der Icons fest (Optionen: `id`,`name`,`label`,`code`,`createdate`)
* `sort-direction` - legt die Sortierungsrichtung fest (`asc` für aufsteigend oder `desc` für absteigend)
* `icons` - legt die Liste der zur Auswahl zugelassen Icons fest; erlaubt Platzhalter mit `*` (Bsp.: `question,user*,*virus*,*-up` - lädt das Icon
 `question` explizit, alle Icons die mit `user` beginnen, alle Icons die irgendwo im Namen `virus` enthalten und alle Icons, die auf `-up` enden)
* `hide-search` - Suchfeld im Kopf des Picker Popups wird versteckt [0, 1]
* `hide-latest-used` - zuletzt benutzte Icons werden in einem Cookie gespeichert; in der "zuletzt benutzt" Sektion werden bis zu 2 Zeilen davon gezeigt; wenn das Feld auf 1 gesetzt wird, ist diese Sektion im Widget versteck [0, 1]
* `class` - CSS-Klasse, die am äußersten Wrapper-Element des Picker Widgets für eigene Anpassungen angehängt wird
* `icon-class` - CSS-Klasse, die an den Icon Buttons für eigene Anpassungen angehängt wird
* `onbeforeselect` - Event-Handler für den Zeitpunkt, bevor ein Icon in das zugehörige Eingabefeld eingecheckt wird (`return false` verhindert das
 Einfügen in das Eingabefeld, der 1. Funktionsparameter ist das auslösende Button-Object, der 2. Parameter das Ziel-Objekt)
* `onselect` - Event-Handler für den Zeitpunkt, nachdem ein Icon in das zugehörige Eingabefeld eingecheckt wurde (der 1. Funktionsparameter ist der Name des Icons, der 2. Parameter das gelöscht-Flag, der 3. Parameter das Ziel-Objekt)
* `onbeforeshow` - Event-Handler: Vor dem Anzeigen des Widgets | 1. Param: Ziel-Feld als DOM-Object
* `onshow` - Event-Handler: Nach dem Anzeigen des Widgets | 1. Param: Ziel-Feld als DOM-Object
* `onbeforehide` - Event-Handler: Vor dem Ausblenden des Widgets | 1. Param: Ziel-Feld als DOM-Object
* `onhide` - Event-Handler: Nach dem Ausblenden des Widgets | 1. Param: Ziel-Feld als DOM-Object

## Future Plans / TODO

* Wahl eines Subsets direkt am Ort der Initialisierung (Überschreibung des Standard-Pakets)
* Extension Points für REX API Rückgaben (überhaupt möglich?)
* Preview-Overlay bei Mouseover über Icon-Value im Text-Feld > zeigt, wie das aktuell gesetzte Icon aussieht ohne den Picker zu öffnen
* evtl. Webfont-URL in den .css-Files manipulieren, sodass der korrekte absolute Pfad eingetragen wird. Damit wären _"elegantere"_ Einbindungen im FE möglich, ohne den Speicherort der Paketdaten offen zu legen (via index.php mit URL-Params)
* FA6 Support sobald finale Package-Struktur releast ist
