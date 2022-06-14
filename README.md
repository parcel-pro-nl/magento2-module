=== Parcel Pro ===
Tags: Shipping, Verzending, Pakketten, PostNL, DHL, DPD, UPS, GLS, Multi Carrier, Shops United Parcel Pro, Parcelpro
Requires at least: Magento 2.x
Tested up to: 2.4.0
Stable tag: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Parcel Pro heeft een module ontwikkeld die geïnstalleerd kan worden in de backoffice van Magento. Hiermee kunt u heel gemakkelijk orders inladen in ons verzendsysteem. Dit zorgt ervoor dat het verzendproces efficiënter wordt en het helpt u bij het verwerken van meerdere orders en zendingen.

De handleiding is te vinden op https://www.parcelpro.nl/koppelingen/magento
Deze repository zal geplaatst moeten worden in uw app/code/Parcelpro/Shipment directory geplaatst moeten worden.

Bij vragen kunt u contact opnemen via https://www.parcelpro.nl/over-ons/

== Changelog ==

## V.2.12.0- 2022-03-29
#### New
- DPD toegevoegd aan vervoerders, met parcelshop.

## V.2.11.1- 2021-12-14
#### Fixes
- Config.xml string value aangepast aan de hand van de commit van frank-bokdam.
- Composer.json aangepast voor betere ondersteuning aan de hand van de commit van govereem.

## V.2.11.0- 2021-03-29
#### Nieuwe functionaliteiten
- Bij orders kunnen nu individuele verzendmethodes gekozen uit de custom regels en opgeslagen worden.
#### Fixes
- Verzendmethodes worden nu beter geladen aan de hand van de commit van Tjitse-E.
- Lowercase composer.json aan de hand van meerdere requests.

## V.2.10.0- 2020-09-17
#### Fixes
- Storeview aanpassing

## V.2.9.0- 2020-09-17

#### Fixes
- Locatiekiezer pop-up op mobiel scherm
- Straat en huisnummer doorgeven.
- Multi store config fixes


## V.2.8.3 - 2020-03-12

#### Fixes
- HTTP -> HTTPS aangepast voor verbeterde beveiligingsmaatregelen.

## V.2.8.2 - 2019-12-03

#### Fixes

- Poort uit de locatiekiezer
- Gebruik unserialize van Magento

## V.2.8.1 - 2019-09-18

#### Fixes

- Afdrukken en aanmelden van zendingen(batch)
- Status aanpassen na afdrukken


## V.2.8.0 - 2019-08-30

#### Nieuwe functionaliteiten

- Installeren vanuit Git
- Firecheckout ondersteuning.

## V.2.7.0 - 2018-11-13

#### Nieuwe functionaliteiten

- Totaalprijzen incl / excl btw gebruiken voor verzendregels

#### Fixes

- Status na afdrukken fix (magento t/m 2.2.1 uitgesloten)
- Parcelshop keuze en factuuradres
- Parcelpro.js parcelpro-modal.js fixes m.b.t inladen.

## V.2.6.0 - 2018-09-12

#### Nieuwe functionaliteiten

- Verzendopties achteraf via de backend wijzigen.
- Status na zendinglabel afdrukken
- Auto inladen bij status
- BTW tarief per regel
- Verzendlabels in bulk afdrukken

#### Fixes

- Order Id column type in databasel tabel.

## V.2.5.2

#### Nieuwe functionaliteiten

- Ondersteuning voor Xtento module.
- Zendingstype retournerern via api
- Ondersteuning voor lotusbreath checkout

## V.2.5.1

#### Fixes

- Dubbel waarde van het grandtotal door dubbel berekenen van totalen.

## V.2.5.0

#### Nieuwe functionaliteiten

- Ondersteuning Firecheckout module.
- Ondersteuning modman installatie.

#### Fixes

- Niet tonen van Sameday verzendtitel.
- Backed label url genereren.
- Automatisch aanmelden wanneer er geen status is ingesteld.

## V.2.4.0

#### Nieuwe functionaliteiten

- Eigen labels definiëren voor verzendmethoden.
- Acties na bepaalde status
- Meerdere tariefregels per verzendmethode

#### Fixes

- Cadeaubon berekening
- Backend verzendmethode berekening

## V.2.3.2

#### Fixes

- Trackinggegevens juist ophalen.
- Automatisch aanmelden fix

## V.2.3.0

#### Nieuwe functionaliteiten

- Automatisch aanmelden

## V.2.2.0

#### Nieuwe functionaliteiten

- Bulk acties

## V.2.1.0

#### Nieuwe functionaliteiten

- Afhaallocatie kiezen voor zowel DHL als PostNL

## V.2.0.0

#### Nieuwe functionaliteiten

- Verzendlabel afdrukken
- Ondersteuning voor DPD, UPS, Same Day
- Herstructurering van de code

## V.1.0.0

#### Nieuwe functionaliteiten

- Verzendmethoden aanmaken
- Bestellingen aanmelden in het verzendsysteem
- Ondersteuning voor DHL, PostNL
