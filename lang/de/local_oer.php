<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Open Educational Resources Plugin
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addpersonbtn'] = 'Person hinzufügen';
$string['all'] = 'Alle Elemente';
$string['allowed'] = '<p>Durch den Upload Ihrer Lehr- und Lernmaterialien machen Sie ' .
        'diese über das Bibliotheksservice offen und frei zugänglich, ' .
        'sodass andere Lehrende und Lernende weltweit ' .
        'diese verwenden können.<br> ' .
        'Bitte beachten Sie, dass diese Materialien unter einer ' .
        'offenen Lizenzierung stehen und damit die geltenden ' .
        'Urheberrechtsbestimmungen eingehalten sind.</p>';
$string['amount'] = 'Elemente pro Seite';
$string['applicationprofile'] = 'Applikationsprofil';
$string['applicationprofile_description'] =
        'Wählen Sie das Applikationsprofil aus, das für die Erzeugung der Metadaten verwendet wird. <br>' .
        '<ul>' .
        '<li>v1.0.0: Profil verwendet bis zu local_oer Plugin Version v2.2.1 (2023062800). ' .
        'Unterstützt nur Dateien, die direkt in Moodle gehostet werden.' .
        'Für Abwärtskompatibilität verwenden</li>' .
        '<li>v2.0.0: Unterstützung für verschiedene Quellen. Verwendet neuen Identifier. Nicht kompatibel zu älteren Versionen.</li>' .
        '</ul>' .
        'Weitere Informationen über die Struktur der Metadaten finden Sie in der ' .
        '<a href="https://github.com/llttugraz/moodle-local_oer/blob/main/README.md" target="_blank">ReadMe</a>';
$string['author'] = 'Autor:in';
$string['card'] = 'Karten';
$string['cb_allowedlist'] = 'Freigabeliste';
$string['cb_allowedlist_desc'] = 'Dient zur Verwaltung einer Benutzerliste mit Zugang zum Metadaten-Editor';
$string['chart'] = 'Tabelle';
$string['configtime'] = 'Veröffentlichungszeitpunkt festlegen';
$string['confirmperson'] = 'Zum Bestätigen <strong>Enter</strong> drücken.';
$string['contactsupport'] = 'Für weitere Informationen kontaktieren Sie bitte ' .
        '<a href="mailto:{$a->support}">{$a->support}</a>.';
$string['contents'] = 'Inhaltsverzeichnis';
$string['context'] = 'Kontext';
$string['context_help'] = 'Der Bildungskontext, für den diese Datei ' .
        'konzipiert/geschrieben wurde.';
$string['contextnotset'] = 'Kontext ist nicht ausgewählt';
$string['contextset'] = 'Kontext ist ausgewählt';
$string['correctlicense'] = 'Lizenz: {$a->license}';
$string['coursecustomfields'] = 'Benutzerdefinierte Kursfelder hinzufügen';
$string['coursecustomfields_description'] = 'Wenn diese Option aktiviert ist, werden benutzerdefinierte ' .
        'Kursfelder (course customfields) aus dem System gelesen und zu ' .
        'den Kursmetadaten des Moodle-Kurses hinzugefügt.';
$string['coursecustomfieldsignored'] = 'Benutzerdefinierte Felder ignorieren';
$string['coursecustomfieldsignored_description'] = '<p>Ignorieren Sie benutzerdefinierte Felder, ' .
        'indem Sie sie diese in diesem Mehrfachauswahlfeld auswählen. ' .
        'Standardmäßig werden alle Felder zu den Kursmetadaten ' .
        'hinzugefügt, wenn benutzerdefinierte Felder für ' .
        'das OER-Plugin aktiviert sind.</p>' .
        '<p>Beachten Sie, dass die Sichtbarkeitseinstellung ebenfalls ' .
        'angewendet wird und die Felder eventuell nicht angezeigt ' .
        'werden, auch wenn sie in dieser Einstellung angezeigt werden.</p>' .
        '<p>Feldformat: {Feldname} ({Kategorie} {Sichtbarkeit})</p>';
$string['coursecustomfieldsvisibility'] = 'Sichtbarkeit von benutzerdefinierten Feldern';
$string['coursecustomfieldsvisibility_description'] = '<p>Die Sichtbarkeitsstufe gibt an, welche Felder zu den ' .
        'Metadaten hinzugefügt werden. Wenn ein benutzerdefiniertes ' .
        'Feld in Moodle eingerichtet wird, muss festgelegt werden, ' .
        'welche Nutzer das Feld sehen können. Hierfür gibt es drei ' .
        'Optionen. Im OER-Kontext können diese drei Optionen wie ' .
        'folgt verwendet werden.</p>' .
        '<ul>' .
        '<li>"Alle": Nur Felder, die mit diesem Status markiert sind, ' .
        'werden hinzugefügt</li>' .
        '<li>"Trainer:innen": Die Felder "Trainer:innen" und "Alle" ' .
        'werden hinzugefügt</li>' .
        '<li>"Nicht-sichtbar": Alle Felder werden hinzugefügt ' .
        '(einschließlich der anderen Optionen)</li>' .
        '</ul>';
$string['courseinfobutton'] = 'Kurs-Metadaten';
$string['courseinfoformadditionalhelp'] = 'Die Kursmetadaten werden regelmäßig synchronisiert. ' .
        'Um synchronisierte Informationen zu überschreiben, aktivieren ' .
        'Sie die Kontrollkästchen neben den jeweiligen Textfeldern.';
$string['courseinfoformexternhelp'] = 'Sollten Sie hier mehr als einen Kurs sehen, so bedeutet das, ' .
        'dass dieser Kurs mit einem oder mehreren externen Kursen ' .
        'verbunden ist.' .
        'In diesem Fall können Sie einzelne Kurse davon ignorieren. ' .
        'Deren Metadaten werden dann nicht bei den Dateien angehängt. ' .
        'Es müssen zumindest die Metadaten eines Kurses angehängt werden.';
$string['courseinfoformhelp'] = 'Zu den Metadaten jeder Datei werden auch Informationen des ' .
        'Kurses angehängt in welchem sie verwendet wird. Hier können ' .
        'die Metadaten des Kurses editiert werden.';
$string['coursename'] = 'Titel';
$string['coursename_help'] = 'Titel des Kurses';
$string['coursetofile'] = 'Kursmetadaten bei Dateien überschreiben';
$string['coursetofile_description'] = 'In Kursen können die Kursmetadaten bearbeitet werden. Dabei kann ' .
        'entschieden werden ob die Metadaten des Moodle Kurses und ' .
        'externer Kurse welche mit einem Moodle Kurs verbunden sind ' .
        '(Subplugin) an Dateien angehängt werden.' .
        'Ist diese Einstellung eingeschaltet, kann dies zusätzlich pro ' .
        'Datei überschrieben werden. Ausserdem können die Kursmetadaten ' .
        'aus anderen Kursen, welche die selbe ' .
        'Datei verwenden angehängt werden.';
$string['coursetofile_info'] = 'In diesem Formular sind alle Kurs-Metadaten aufgelistet ' .
        'welche für diese Datei zur Verfügung stehen. Hier können ' .
        'sie die Kurseinstellungen, welche Kurs-Metadaten bei dieser ' .
        'Datei angehängt werden, überschreiben.';
$string['custom'] = 'Benutzerdefinierte Zeit';
$string['customdates'] = 'Benutzerdefinierte Zeitpunkte';
$string['customdates_error'] = 'Falsches Format! <br>';
$string['customdates_help'] = 'Einzelner Eintrag: DD.MM<br>' .
        'Mehrfach: DD.MM;DD.MM;DD.MM<br>' .
        '(DD Tag, MM Monat)';
$string['customfield_help'] = 'Benutzerdefinierte Kursfelder können auf ähnliche Weise wie die ' .
        'Standard-Kursfelder hinzugefügt werden. Sie können auch ' .
        'überschrieben werden, so dass ein anderer Wert als der im Kurs ' .
        'eingestellte verwendet werden kann. ' .
        'Weiters ist es auch möglich diese zu ignorieren, um sie nicht zu ' .
        'den Kurs-Metadaten der freigegebenen OER-Objekte hinzuzufügen.';
$string['customfieldcategory_help'] = '<p>Dies ist der Name einer benutzerdefinierten Feldkategorie, ' .
        'darunter sind die Felder der Kategorie zu sehen.</p>' .
        '<p>Benutzerdefinierte Felder können nicht überschrieben werden. ' .
        'Bearbeiten ist nur in den Kurseinstellungen möglich.</p>';
$string['default'] = 'Standard';
$string['deleted'] = 'Gelöscht';
$string['deleted_help'] = 'Die automatische Synchronisation der Kurs-Metadaten hat diesen ' .
        'Kurs als gelöscht markiert, weil die externe Quelle nicht mehr' .
        'mit diesem Moodle-Kurs verbunden ist.' .
        'Da die Metadaten manuell bearbeitet wurden,' .
        'wurde der Eintrag markiert und nicht gelöscht.' .
        'Wenn Sie die Einträge nicht mehr benötigen,' .
        'können Sie die Kontrollkästchen der' .
        'bearbeiteten Felder deaktivieren und die Metadaten werden' .
        'bei der nächsten Synchronisation gelöscht.';
$string['description'] = 'Inhalt';
$string['description_help'] = 'Beschreibung des Kurses. Was ist der Inhalt des Kurses.';
$string['diagram'] = 'Diagramm';
$string['editor'] = 'Editor';
$string['emailsubject'] = 'E-Mail Betreff';
$string['emailsubject_desc'] = 'E-Mail-Betreff vorausgefüllt für den Support.';
$string['error_body'] = 'Es ist ein Fehler aufgetreten, bitte laden Sie die Seite erneut. ' .
        'Wenn der Fehler weiterhin besteht kontaktieren Sie ' .
        'bitte ihren Administrator.';
$string['error_license'] = 'Falsche Lizenz zum Veröffentlichen ausgewählt.';
$string['error_message'] = 'Fehlermeldung';
$string['error_upload_author'] = 'Eine Person mit der Rolle "{$a->roles}" wird benötigt.';
$string['error_upload_classification'] = 'Mindestens ein Element muss für die Veröffentlichung ausgewählt werden';
$string['error_upload_context'] = 'Zum Veröffentlichen muss ein Kontext gesetzt werden';
$string['error_upload_description'] = 'Ein Abstrakt ist für die Veröffentlichung erforderlich.';
$string['error_upload_language'] = 'Für die Veröffentlichung ist eine Sprache erforderlich.';
$string['error_upload_license'] = 'Zum Veröffentlichen von Dateien ist eine ' .
        'Creative Commons oder die Public Domain Lizenz erforderlich' .
        'Sie müssen entweder das Häkchen bei "Freigabe" entfernen ' .
        'oder eine entsprechende Lizenz einstellen.';
$string['error_upload_resourcetype'] = 'Eine Ressource muss für die Veröffentlichung ausgewählt werden.';
$string['error_upload_tags'] = 'Mindestens ein Tag ist für die Veröffentlichung erforderlich.';
$string['errorempty'] = 'Dieses Feld wird benötigt.';
$string['exam'] = 'Prüfung';
$string['exercise'] = 'Übung';
$string['experiment'] = 'Experiment';
$string['extendedpullservice'] = 'Erweiterter Webservice';
$string['extendedpullservice_desc'] = 'Standardmäßig werden bei einem aktivierten Pull-Webservice nur ' .
        'die freigegebenen Dateien angezeigt. Ein zusätzliches Upload ' .
        'Plugin ist notwendig, um die Dateien in ein externes Repository ' .
        'hochzuladen und als veröffentlicht zu markieren. Der erweiterte ' .
        'Webservice zeigt alle Dateien an, die alle Voraussetzungen für ' .
        'die Veröffentlichung erfüllen und zur Veröffentlichung markiert sind.';
$string['figure'] = 'Abbildung';
$string['filecount'] = 'Elemente sind verfügbar';
$string['filecount_info'] = 'Die Anzahl der verfügbaren Elemente hängt von den Filtereinstellungen ab';
$string['filedescription'] = 'Abstrakt';
$string['filedescription_help'] = 'Ein kleiner Überblick über die Inhalte der Datei';
$string['filetype'] = 'Typ';
$string['graphic'] = 'Grafik';
$string['highereducation'] = 'Hochschulbildung';
$string['ignore'] = 'Ignoriert';
$string['ignore_help'] = 'Die Datei wird als ignoriert angezeigt und an das Ende der Dateiliste sortiert.';
$string['ignorecourse'] = 'Ignorieren';
$string['ignorecourse_help'] = 'Wird der Kurs ignoriert, werden die Metadaten des Kurses bei der ' .
        'Veröffentlichung nicht berücksichtigt.';
$string['ignoredcourse'] = 'Die Metadaten dieses Kurses ausschliessen';
$string['isignored'] = 'Ignoriert';
$string['language'] = 'Sprache';
$string['language_help'] = 'Welche Sprache wird benutzt.';
$string['lastchange'] = 'Zuletzt modifiziert:';
$string['lecture'] = 'Vortrag';
$string['lecturer'] = 'Vortragende/r';
$string['lecturer_help'] = 'Die Vortragenden des Kurses sind nicht unbedingt die Autoren der Datei.';
$string['license'] = 'Lizenz';
$string['license_help'] = 'Um eine Datei zu veröffentlichen, muss eine ' .
        'Creative-Commons Lizenz oder Public Domain verwendet werden.';
$string['licensenotfound'] = 'Lizenz nicht gefunden';
$string['licensereplacement'] = 'Ersetzen von Lizenz Kurznamen';
$string['licensereplacement_description'] = 'Für externe Systeme kann das Lizenzkürzelsystem von Moodle ein ' .
        'wenig verwirrend sein. In diesem Feld kann ein Mapping für den ' .
        'von Moodle verwendeten Kurznamen und für den daraus ' .
        'resultierenden Kurznamen in den Metadaten der Dateien definiert ' .
        'werden. Ein Eintrag pro Zeile im Format: <em>Kurzname=>Ersetzung</em>';
$string['list'] = 'Liste';
$string['log_oer'] = 'OER Logs and errors';
$string['logheading'] = 'Log';
$string['manage_oer'] = 'OER Freigabe';
$string['manageview'] = 'OER Einstellungen und Freigabeliste';
$string['markedforupload'] = 'Wurde zum Veröffentlichen vorgemerkt';
$string['message'] = 'Nachricht';
$string['messageprovider:requirementschanged'] = 'OER Metadaten Anforderungen haben sich geändert';
$string['metadataaggregator'] = 'Kurs-Metadaten';
$string['metadataaggregator_description'] = 'Welches Plugin soll die Metadaten der Kurse laden und verarbeiten.';
$string['metadatanotwritable'] = 'Die Metadaten dieser Datei können nicht editiert werden.';
$string['metadatanotwritable0'] = 'Ein Fehler ist aufgetreten. Diese Datei wurde in mehreren Kursen editiert.';
$string['metadatanotwritable2'] = 'Diese Datei wird bereits in einem anderen Kurs bearbeitet.';
$string['metadatanotwritable3'] = 'Diese Datei wurde bereits veröffentlicht und kann deshalb nicht mehr editiert werden.';
$string['minimumchars'] = 'Mindestens {$a} Zeichen erforderlich';
$string['moreinformation'] = 'Weitere informationen';
$string['multiplecourses'] = 'Mehrere Kurse verwenden diese Datei';
$string['multiplecoursestofile'] = 'Mehrere Kurse verwenden diese Datei. Sie können auch ' .
        'Kurs-Metadaten hinzufügen, die zu einem anderen Kurs ' .
        'gehören als dem, in dem die Datei bearbeitet wird.';
$string['narrative'] = 'Erzählung';
$string['next_release'] = 'Nächste Veröffentlichung von Dateien';
$string['no_files_body'] = 'Es wurden keine Dateien im Kurs gefunden.';
$string['no_files_description'] = 'Es werden nur Dateien aus der "Datei" oder "Ordner" Aktivität gelistet';
$string['no_files_filter'] = 'Mit dieser Filtereinstellung werden keine Dateien gefunden.';
$string['no_files_heading'] = 'Keine Dateien gefunden';
$string['no_value'] = 'Basis Kurs-Metadaten Aggregator';
$string['nocourseinfo'] = 'Die Kurs-Metadaten dieses Kurses sind noch nicht für ' .
        'OER-Zwecke synchronisiert worden. Wenn Sie die Kurs-Metadaten ' .
        'dieses Kurses verwenden möchten, öffnen Sie bitte die OER-Ansicht ' .
        'innerhalb des Kurses und bearbeiten Sie die Metadaten.';
$string['nofieldsincat'] = 'Diese Kategorie enthält keine Felder, die angezeigt werden können.';
$string['noignore'] = 'Nicht ignoriert';
$string['noinfo'] = 'Keine zusätzlichen Informationen';
$string['nopreference'] = 'keine Voreinstellung';
$string['nopreferencefilter'] = 'Voreinstellungen deaktiviert';
$string['noprefsetyet'] = 'Für diesen Kurs wurden noch keine Voreinstellungen vorgenommen. ' .
        'Wenn Sie vordefinierte Werte für einige der Metadatenfelder ' .
        'verwenden möchten, finden Sie das Einstellungsmenü in der ' .
        'Navigationsleiste oberhalb der Dateien.';
$string['norelease'] = 'Nicht zur Veröffentlichung freigegeben';
$string['noselection'] = 'keine Auswahl';
$string['notactive'] = 'Dieser Kurs ist nicht aktiv, bitte aktivieren Sie ihn ' .
        'über die Schaltfläche "Kurs aktivieren".';
$string['notallowedtext'] = 'Text für Benutzer:innen ohne Zugang';
$string['notallowedtext_desc'] = 'Der Zugang zum Datei Metadaten Editor wird von einer ' .
        'Zugangsliste gesteuert (entweder mit strikter Erlaubnis, ' .
        'oder betroffene Benutzer:innen wurden gesperrt). Dieses ' .
        'Textfeld wird diesen Benutzer:innen angezeigt. Dies ist als ' .
        'Einstellung verfügbar damit organisationsbezogene Daten wie ' .
        'z.B. Support E-Mail oder Telefonnummer angezeigt werden können.';
$string['notmarkedforupload'] = 'Wurde nicht zum Veröffentlichen vorgemerkt';
$string['objectives'] = 'Lernziel';
$string['objectives_help'] = 'Was sind die Lernziele des Kurses.';
$string['oer:edititems'] = 'Berechtigung Metadaten von Dateien zu bearbeiten.';
$string['oer:manage'] = 'OER Einstellungen verwalten';
$string['oer:viewitems'] = 'Berechtigung OER Dateien zu betrachten.';
$string['oer_intro'] = '<blockquote>' .
        '<p>"Open Educational Resources (OER) sind freie Bildungsmaterialien,' .
        ' d.h. Lehr- und Lernmaterialien, die frei zugänglich sind und dank entsprechender ' .
        'Lizenzierung (oder weil sie gemeinfrei sind) ohne zusätzliche Erlaubnis bearbeitet, ' .
        'weiterentwickelt und weitergegeben werden dürfen."</p>' .
        '<p><cite>Bündnis Freie Bildung, 2015</cite></p>' .
        '</blockquote>';
$string['oer_link'] = 'OER';
$string['oer_settings'] = 'Plugin Einstellungen';
$string['oermetadataheading'] = 'Einstellungen zu Metadaten';
$string['oermetadataheading_desc'] = 'Einige Einstellungen für Metadaten wie die Auswahl eines ' .
        'zusätzlichen Subplugins. ';
$string['oerreleaseheading'] = 'Einstellungen zur Veröffentlichung';
$string['oerreleaseheading_desc'] = 'Einstellungen, die die Freigabefunktionalität betreffen';
$string['oerusers'] = 'Authorisierte Benutzer:innen';
$string['onecourseinfoneeded'] = 'Es sind zumindest die Metadaten eines Kurses erforderlich. ' .
        'Alle können nicht ignoriert werden';
$string['oneeditorselectederror'] = 'Mindestens eine der Optionen für die Metadaten des ' .
        'editierenden Kurses muss ausgewählt sein.';
$string['organisation'] = 'Organisation';
$string['organisation_desc'] = 'Informationen über die Organisation, die das OER-Plugin nutzt. ' .
        'Diese Informationen werden zumindest den Nutzer:innen angezeigt, ' .
        'die die OER-Funktionalität noch nicht nutzen dürfen.';
$string['organisation_help'] = 'Name der Organisation welche den Kurs anbietet.';
$string['organisationemail'] = 'E-Mail';
$string['organisationemail_desc'] = 'E-Mail Adresse des Supports ...';
$string['organisationheading'] = 'Organisation';
$string['organisationname'] = 'Name';
$string['organisationname_desc'] = 'Name der Organisation';
$string['organisationphone'] = 'Telefon';
$string['organisationphone_desc'] = 'Telefonnummer des Supports ...';
$string['origin'] = 'Quelle';
$string['overwrite'] = 'Überschreiben';
$string['person'] = 'Person(en)';
$string['person_help'] = 'Die Schaltfläche öffnet ein Formular, in dem Sie den Vor- und Nachnamen einer Person ' .
        'eingeben und ihre Rolle für dieses Element auswählen können.' .
        '<p>Mehrere Personen können eingegeben werden.</p>' .
        '<p>Eingegebene Namen werde oberhalb der Schaltfläche angezeigt.</p>' .
        '<p>Durch Klick auf die Boxen können Namen wieder entfernt werden.</p>';
$string['persondefined'] = 'Autor:in / Verleger:in ist eingetragen';
$string['personmissing'] = 'Autor:in / Verleger:in fehlt';
$string['pluginname'] = 'Open Educational Resources';
$string['potusers'] = 'Benutzer:innen auswählen';
$string['potusersmatching'] = 'Authorisierte Benutzer:innen';
$string['prefclassification'] = 'Zusätzliche Schlagwörter (Voreinstellung)';
$string['prefdisable'] = 'Voreinstellungen deaktivieren';
$string['prefenable'] = 'Voreinstellungen aktivieren';
$string['preferenceactive'] = 'Voreinstellungen sind aktiviert';
$string['preferencebutton'] = 'Voreinstellungen';
$string['preferencedefault'] = 'Voreinstellung ein/aus';
$string['preferencedefault_desc'] = 'Die Voreinstellungen für den Kurs werden unabhängig von den ' .
        'Datei-Metadaten gespeichert. Wenn eine neue Dateiliste in ' .
        'einem Kurs erstellt wird, werden keine Datei-Metadaten ' .
        'gespeichert. Die Option, ob eine Datei Voreinstellungen ' .
        'verwendet, wird in der Datei-Metadatentabelle gespeichert. ' .
        'Diese Einstellung ist notwendig, um eine Voreinstellung zu ' .
        'setzen, bevor die Datei-Metadaten existieren. ' .
        'Wenn sie aktiviert ist, geschehen zwei Dinge:' .
        '<ul>' .
        '<li>Bei der Ausgabe im Frontend steht bei jeder Datei ob ' .
        'Voreinstellungen benutzt werden </li> ' .
        '<li>Wenn die Einstellungen den Freigabeanforderungen entsprechen, ' .
        'können Dateien freigegeben werden, ohne dass ihre Metadaten ' .
        'bearbeitet werden</li > ' .
        '</ul>';
$string['preferencedisabled'] = 'Voreinstellungen sind für diese Datei <strong>deaktiviert</strong>.';
$string['preferenceenabled'] = 'Voreinstellungen sind für diese Datei <strong>aktiviert</strong>.' .
        'Felder welche in den Voreinstellungen ausgefüllt werden ' .
        'sind hier gesperrt.';
$string['preferencefilter'] = 'Voreinstellungen aktiviert';
$string['preferenceinfoformhelp'] = '<p class="alert alert-info">Die ausgefüllten Felder dieses ' .
        'Voreinstellungsformulars werden als Basiswert für ' .
        'Dateien verwendet, welche zum ersten mal editiert werden.</p>';
$string['preferencenotactive'] = 'Voreinstellungen sind deaktiviert';
$string['preferences'] = 'Voreinstellungen';
$string['preferenceset'] = '(Voreinstellung)';
$string['preferenceset_help'] = 'Dieses Feld wird durch die Voreinstellungen gesteuert. ' .
        'Wenn Sie dieses Feld ändern möchten, können Sie entweder ' .
        'die Voreinstellung für diese Datei deaktivieren oder die ' .
        'Voreinstellung so bearbeiten, dass dieses Feld nicht erfasst wird.';
$string['preferencetags_help'] = 'In den Voreinstellungen wurden zusätzliche Tags definiert. ' .
        'Diese Tags werden zu den Tags hinzugefügt, die im Standardfeld ' .
        'dieses Formulars definiert sind. Wenn Sie diese Tags in dieser ' .
        'Datei nicht verwenden wollen, können Sie entweder die ' .
        'Voreinstellung für diese Datei deaktivieren oder die ' .
        'Voreinstellung bearbeiten, um einige oder alle Tags zu ' .
        'entfernen. Doppelte Tags in den Einstellungen werden beim ' .
        'Freigeben der Dateien bereinigt.';
$string['prefperson'] = 'Person(en) (Voreinstellung)';
$string['prefresetbtn'] = 'Zurücksetzen';
$string['prefresettext'] = 'Felder, welche in Voreinstellungen gesetzt sind, zurücksetzen auf ' .
        'Werte der Voreinstellungen. Andere Felder werden nicht geändert. ' .
        'Formular muss gespeichert werden um diese Änderungen zu übernehmen.';
$string['preftags'] = 'Schlagwörter (Voreinstellung)';
$string['presentationslide'] = 'Präsentationsfolie';
$string['pressenter'] = 'Zum Bestätigen des Feldes bitte die Enter Taste drücken.';
$string['privacy:metadata:additionaldata'] = 'Additional metadata from subplugins stored as json object';
$string['privacy:metadata:classification'] = 'Tags from external classifications, stored as json';
$string['privacy:metadata:component'] = 'Frankenstyle name of plugin';
$string['privacy:metadata:contenthash'] = 'Moodle contenthash of file';
$string['privacy:metadata:context'] = 'Educational context ';
$string['privacy:metadata:coursecode'] = 'Course identifier, moodle shortname, else the external identifier of this course';
$string['privacy:metadata:courseid'] = 'Moodle courseid';
$string['privacy:metadata:coursemetadata'] = 'Metadata of linked courses added as json object';
$string['privacy:metadata:coursename'] = 'The moodle coursename or the external coursename';
$string['privacy:metadata:coursename_edited'] = 'Bool: coursename field edited or not';
$string['privacy:metadata:customfields'] = 'Custom fields';
$string['privacy:metadata:deleted'] = 'Mark entries as deleted when the course courseinfo changes';
$string['privacy:metadata:description'] = 'General description of course and its topics';
$string['privacy:metadata:description_edited'] = 'Bool: description field edited or not';
$string['privacy:metadata:external_courseid'] = 'If external webservice is used for information, this field can be used to link the unique id of external course';
$string['privacy:metadata:external_sourceid'] = 'If known, the external predecessor id of this course';
$string['privacy:metadata:identifier'] = 'Identifier of element';
$string['privacy:metadata:ignored'] = 'Bool, if a course has multiple external courses linked, all except one can be ignored for oer items';
$string['privacy:metadata:language'] = 'ISO 639-1 language code this course uses';
$string['privacy:metadata:language_edited'] = 'Bool: language field edited or not';
$string['privacy:metadata:lecturer'] = 'List of lecturers of the course, CSV Format: Firstname Lastname';
$string['privacy:metadata:lecturer_edited'] = 'Bool: lecturer field edited or not';
$string['privacy:metadata:license'] = 'Shortname of license';
$string['privacy:metadata:local_oer_courseinfo'] = 'Metadata of course';
$string['privacy:metadata:local_oer_coursetofile'] = 'Mapping which course metadata objects are added to which file';
$string['privacy:metadata:local_oer_elements'] = 'Logs ';
$string['privacy:metadata:local_oer_log'] = 'Log information of plugin name and message';
$string['privacy:metadata:local_oer_preference'] = 'File metadata preferences per course';
$string['privacy:metadata:local_oer_snapshot'] = 'Snapshot of released metadata is stored';
$string['privacy:metadata:local_oer_userlist'] = 'Die Benutzer:innen ID wird für die Freigabe/Verweigerungs-Liste gespeichert.';
$string['privacy:metadata:local_oer_userlist:timecreated'] = 'Zeitpunkt der Freigabe/Verweigerung';
$string['privacy:metadata:local_oer_userlist:type'] = 'Art der Freigabe';
$string['privacy:metadata:local_oer_userlist:userid'] = 'Benutzer:innen ID';
$string['privacy:metadata:message'] = 'Detailed log message';
$string['privacy:metadata:objectives'] = 'Description of the main goals of the course';
$string['privacy:metadata:objectives_edited'] = 'Bool: objective field edited or not';
$string['privacy:metadata:organisation'] = 'Whats the name of the organisation the course belongs to';
$string['privacy:metadata:organisation_edited'] = 'Bool: organisation field edited or not';
$string['privacy:metadata:persons'] = 'Authors or Publishers of this file';
$string['privacy:metadata:releasehash'] = 'SHA256 of metadata to compare if something has changed';
$string['privacy:metadata:releasenumber'] = 'Number of the release';
$string['privacy:metadata:releasestate'] = 'Release or ignore file';
$string['privacy:metadata:resourcetype'] = 'Resource type';
$string['privacy:metadata:state'] = 'State of what to do with this entry, states are defined in code';
$string['privacy:metadata:structure'] = 'Type of course';
$string['privacy:metadata:structure_edited'] = 'Bool: structure field edited or not';
$string['privacy:metadata:subplugin'] = 'Source plugin of this information';
$string['privacy:metadata:tags'] = 'Tags for this element, comma separated';
$string['privacy:metadata:timecreated'] = 'Time when record was created';
$string['privacy:metadata:timemodified'] = 'Time when record was modified';
$string['privacy:metadata:title'] = 'Element title';
$string['privacy:metadata:type'] = 'Type of the element';
$string['privacy:metadata:typedata'] = 'Type specific data';
$string['privacy:metadata:usermodified'] = 'User ID';
$string['problem'] = 'Problemstellung';
$string['publisher'] = 'Verleger:in';
$string['pullrelease'] = 'Informationen zur Veröffentlichung';
$string['pullrelease_desc'] = 'Die Freigabe von Dateien erfolgt über einen externen Dienst. ' .
        'Dieser Dienst kann jederzeit auf dieses Moodle zugreifen und ' .
        'die zur Freigabe markierten Dateien laden.';
$string['pullservice'] = 'öffentlicher Metadaten Webservice';
$string['pullservice_desc'] = 'Ein Webservice zum Abrufen der Metadaten der veröffentlichten ' .
        'Dateien aus dem Moodle-System. Wenn dieser Webservice für die ' .
        'Freigabe der Dateien verwendet wird, benötigt das externe System ' .
        'ein Token, um damit das Veröffentlichungsdatum in Moodle ' .
        'gesetzt werden kann.';
$string['questionnaire'] = 'Fragebogen';
$string['readyforupload'] = 'Voraussetzungen für die Freigabe:';
$string['reason'] = 'Grund';
$string['released'] = 'Veröffentlicht';
$string['releasehour'] = 'Uhrzeit';
$string['releasehour_help'] = 'Veröffentlichungszeitpunkt.';
$string['releaseplugin'] = 'Verwendet zur Veröffentlichung';
$string['releaseplugin_description'] = 'Wählen Sie ein Subplugin, um das Veröffentlichen zu verwalten. ' .
        'Wenn kein Subplugin zum Hochladen von Dateien in ein Repository ' .
        'installiert ist, steht der Pull-Service des ' .
        'Basis-Plugins zur Verfügung.';
$string['releasetime'] = 'Veröffentlichungsrythmus';
$string['releasetime_help'] = 'Veröffentlichungsrythmus auswählen';
$string['releasetimebutton'] = 'Veröffentlichungszeitpunkt einstellen';
$string['requiredfields'] = 'Notwendige Felder';
$string['requiredfields_desc'] = 'Für das Release benötigte Metadaten/Formularfelder auswählen. ' .
        'Titel, Person(en) und Lizenz werden immer benötigt und ' .
        'sind hier nicht extra angeführt';
$string['requirementschanged_body'] = 'Aufgrund geänderter Richtlinien für die Handhabung von Open ' .
        'Educational Resources wurden die Vorraussetzungen zum ' .
        'Veröffentlichen von Dateien geändert' .
        '<br><br>' .
        'Die Metadaten folgender Dateien müssen im Kurs ' .
        '<a href="{$a->url}">{$a->course}</a> ' .
        'für eine erneute Veröffentlichung angepasst werden:<br><br>';
$string['requirementschanged_small'] = 'Open Educational Resources: Metadaten Anforderungen geändert';
$string['requirementschanged_subject'] = 'Open Educational Resources: Metadaten Anforderungen geändert';
$string['requirementsmet'] = 'Alle Voraussetzungen sind erfüllt.';
$string['requirementsnotmet'] = 'Nicht alle Voraussetzungen sind erfüllt.';
$string['resourcetype'] = 'Ressourcentyp';
$string['resourcetype_help'] = 'Welcher Art ist die Datei?';
$string['role'] = 'Rolle';
$string['role_description'] = 'Die Tabelle zeigt, welche Rollen für welche Elementart verfügbar sind. ' .
        'Die mit einem * (Sternchen) gekennzeichneten Rollen sind für diese Elementart obligatorisch. ' .
        'Wenn eine Person über Voreinstellungen hinzugefügt wird, ' .
        'wird die Person nur zu Elementen hinzugefügt, wo die Rolle verfügbar ist.';
$string['role_help'] = 'Wählen Sie die Rolle für die Person aus. ' .
        'Die Rollen hängen von der Art des Elements ab, daher ist nicht jede Rolle für jedes Element verfügbar.';
$string['searchtitle'] = 'Titel suchen';
$string['selectcc'] = 'CC-Lizenztyp muss ausgewählt werden';
$string['selfassesment'] = 'Selbsteinschätzung';
$string['showmetadata'] = 'Gespeicherte Metadaten anzeigen';
$string['simulation'] = 'Simulation';
$string['snapshottask'] = 'Task to create release snapshots';
$string['source'] = 'Quelle';
$string['state'] = 'Status der Datei';
$string['structure'] = 'Struktur';
$string['structure_help'] = 'Was ist der Modus des Kurses? zB. Vorlesung, Übung, Labor ...';
$string['subpluginsheading'] = 'Liste der installierten Sub-Plugins';
$string['subplugintype_oerclassification_plural'] = 'OER classifications';
$string['subplugintype_oercourseinfo_plural'] = 'OER courseinfos';
$string['subplugintype_oermod_plural'] = 'OER data source modules';
$string['subplugintype_oeruploader_plural'] = ' Upload released files';
$string['tags'] = 'Schlagwörter';
$string['tags_help'] = '<p>Zusätzliche Schlagwörter zur Klassifizierung der Datei.</p>' .
        '<p>Geben Sie ein Schlagwort ein und bestätigen Sie mit ' .
        '<strong>Enter</strong>.</p>' .
        '<p>Es sind mehrere Schlagwörter möglich.</p>' .
        '<p>Sobald Sie ein Schlagwort eingegeben haben, ' .
        'wird es oberhalb des Eingabefeldes angezeigt.</p>' .
        '<p>Sie können ein Schlagwort entfernen, indem Sie auf ' .
        'das Schlagwort oberhalb des Eingabefeldes klicken.</p>';
$string['timediff'] = '{$a->days} Tage, {$a->hours} Stunden und {$a->minutes} Minuten';
$string['title'] = 'Titel';
$string['title_asc'] = 'Titel aufsteigend';
$string['title_desc'] = 'Titel absteigend';
$string['title_help'] = 'Titel der Datei. Initial wird der Dateiname, welcher beim ' .
        'Hochladen der Datei nach Moodle verwendet wurde, angezeigt. ' .
        'Diese Dateinamen sind aber oft wenig aussagekräftig und sollten ' .
        'durch einen Titel ersetzt werden.';
$string['tocourse'] = 'Zum Kurs';
$string['type'] = 'Typ';
$string['updatecourseinfo'] = 'Task zum synchronisieren der Kurs-Metadaten';
$string['updating'] = 'Wird aktualisiert...';
$string['upload'] = 'Zur Veröffentlichung freigegeben';
$string['upload_help'] = 'To mark a file for release some requirements are necessary:' .
        '<ul>' .
        '<li>Der Kontext wurde festgelegt.</li>' .
        '<li>Mindestens eine Person (Autor:in oder Verleger:in) ' .
        'ist eingetragen.</li>' .
        '<li>Die Lizenz ist auf Creative Commons oder ' .
        'Public Domain eingestellt.</li>' .
        '</ul>';
$string['uploaded'] = 'Veröffentlicht am:';
$string['uploadignoreerror'] = 'Zur Freigabe markieren und Ignorieren können nicht gleichzeitig eingeschaltet sein!';
$string['uselicensereplacement'] = 'Lizenz Kurznamen ersetzen';
$string['uselicensereplacement_description'] = 'Wenn diese Option aktiviert ist, steht ein Textfeld zur Verfügung, ' .
        'in dem Alternativen für Moodle-Lizenzkurznamen definiert ' .
        'werden können.';
$string['usersmatching'] = 'Authorisierte Benutzer:innen';
$string['writablefields'] = 'Die Metadaten für die Felder: "{$a->fields}" werden wieder in der ursprünglichen Quelle gespeichert.';
$string['zipnorestriction'] = 'Keine Einschränkung';
$string['zipperfilesize'] = 'ZIP Paket Größe';
$string['zipperfilesize_description'] = 'Paketgröße für ZIP Dateien auswählen . Wichtig:' .
        'Diese Einstellung gibt keine fixe Paketgröße an. Es werden ' .
        'solange Dateien hinzugefügt bis diese Einstellung überschritten ist.';
