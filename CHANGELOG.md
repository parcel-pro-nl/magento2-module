# Changelog

## 2.18.0 - TODO

### New

- Optie om verwachte bezorgdatum voor PostNL en DHL weer te geven toegevoegd

## 2.17.2 - 2024-01-18

### Fixes

- Fix error wanneer `gebruiker_id` config niet bestaat

## 2.17.1 - 2023-11-14

### Fixes

- Fix unserialization error bij het laden van price rules

## 2.17.0 - 2023-10-05

### New

- Parcelshops voor Homerr toegevoegd

## 2.16.0 - 2023-10-03

### New

- Documenteer support voor Magento 2.4.6

### Fixes

- Fix deprecated functionaliteit in `Plugin/PluginBefore.php`

## 2.15.0 - 2023-08-18

### New

- Intrapost toegevoegd

### Fixes

- Parcel Pro kiezer update

## 2.14.0 - 2023-03-22

- isset() toegevoegd aan Controller/Adminhtml/Shipment/Index.php regel 154

## 2.13.0 - 2023-02-02

### New

- PHP 8+ toegevoegd aan composer.json

## 2.12.0 - 2022-03-29

### New

- DPD toegevoegd aan vervoerders, met parcelshop.

## 2.11.1 - 2021-12-14

### Fixes

- Config.xml string value aangepast aan de hand van de commit van frank-bokdam.
- Composer.json aangepast voor betere ondersteuning aan de hand van de commit van govereem.

## 2.11.0 - 2021-03-29

### Nieuwe functionaliteiten

- Bij orders kunnen nu individuele verzendmethodes gekozen uit de custom regels en opgeslagen worden.

### Fixes

- Verzendmethodes worden nu beter geladen aan de hand van de commit van Tjitse-E.
- Lowercase composer.json aan de hand van meerdere requests.

## 2.10.0 - 2020-09-17

### Fixes

- Storeview aanpassing

## 2.9.0 - 2020-09-17

### Fixes

- Locatiekiezer pop-up op mobiel scherm
- Straat en huisnummer doorgeven.
- Multi store config fixes

## 2.8.3 - 2020-03-12

### Fixes

- HTTP -> HTTPS aangepast voor verbeterde beveiligingsmaatregelen.

## 2.8.2 - 2019-12-03

### Fixes

- Poort uit de locatiekiezer
- Gebruik unserialize van Magento

## 2.8.1 - 2019-09-18

### Fixes

- Afdrukken en aanmelden van zendingen(batch)
- Status aanpassen na afdrukken

## 2.8.0 - 2019-08-30

### Nieuwe functionaliteiten

- Installeren vanuit Git
- Firecheckout ondersteuning.

## 2.7.0 - 2018-11-13

### Nieuwe functionaliteiten

- Totaalprijzen incl / excl btw gebruiken voor verzendregels

### Fixes

- Status na afdrukken fix (magento t/m 2.2.1 uitgesloten)
- Parcelshop keuze en factuuradres
- Parcelpro.js parcelpro-modal.js fixes m.b.t inladen.

## 2.6.0 - 2018-09-12

### Nieuwe functionaliteiten

- Verzendopties achteraf via de backend wijzigen.
- Status na zendinglabel afdrukken
- Auto inladen bij status
- BTW tarief per regel
- Verzendlabels in bulk afdrukken

### Fixes

- Order Id column type in databasel tabel.

## 2.5.2

### Nieuwe functionaliteiten

- Ondersteuning voor Xtento module.
- Zendingstype retournerern via api
- Ondersteuning voor lotusbreath checkout

## 2.5.1

### Fixes

- Dubbel waarde van het grandtotal door dubbel berekenen van totalen.

## 2.5.0

### Nieuwe functionaliteiten

- Ondersteuning Firecheckout module.
- Ondersteuning modman installatie.

### Fixes

- Niet tonen van Sameday verzendtitel.
- Backed label url genereren.
- Automatisch aanmelden wanneer er geen status is ingesteld.

## 2.4.0

### Nieuwe functionaliteiten

- Eigen labels definiëren voor verzendmethoden.
- Acties na bepaalde status
- Meerdere tariefregels per verzendmethode

### Fixes

- Cadeaubon berekening
- Backend verzendmethode berekening

## 2.3.2

### Fixes

- Trackinggegevens juist ophalen.
- Automatisch aanmelden fix

## 2.3.0

### Nieuwe functionaliteiten

- Automatisch aanmelden

## 2.2.0

### Nieuwe functionaliteiten

- Bulk acties

## 2.1.0

### Nieuwe functionaliteiten

- Afhaallocatie kiezen voor zowel DHL als PostNL

## 2.0.0

### Nieuwe functionaliteiten

- Verzendlabel afdrukken
- Ondersteuning voor DPD, UPS, Same Day
- Herstructurering van de code

## 1.0.0

### Nieuwe functionaliteiten

- Verzendmethoden aanmaken
- Bestellingen aanmelden in het verzendsysteem
- Ondersteuning voor DHL, PostNL
