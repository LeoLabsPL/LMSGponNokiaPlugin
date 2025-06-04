Dodatek GPON / XGSPON NOKIA do LMS (ver 1.0.5) 

==========================================================
Wymagania:
1. Wersja LMS z git (testowane w dniu 15 Maja 2025 z wersją lMS 27.78)
2. LMS z bazą PostgreSQL 
3. PHP w wersji 5.3 lub nowszej.
4. Moduł snmp dla PHP
5. Polecenia systemowe snmpget, snmpset, snmpwalk z pakietu Net-SNMP (http://www.net-snmp.org)
6. Uprawnienia do odczytu i zapisu przez snmp (wersja do wyboru 1-3) na OLT
7. Soft na OLT w wersji >= 6.x.x

Instalacja:
1. Zawartość paczki umieścić w katalogu plugins w LMS.
2. Utworzyć dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSGponNokiaPlugin
   do katalogu ../plugins/LMSGponNokiaPlugin/img.
   W katalogu img wykonać polecenie: ln -s ../plugins/LMSGponNokiaPlugin/img LMSGponNokiaPlugin
3. Włączyć wtyczkę w zakładce Konfiguracja -> Lista wtyczek
4. Jeśli wtyczka nie jest widoczna wykonać polecenie w katalogu lms: composer update

===========================================================
Instrukcja użytkownika

1. Konfiguracja OLT
   - Dodaj OLT jako urządzenie w osprzęcie sieciowym LMS
   - Wybierz opcję "Nowy OLT" w menu głównym
   - Wybierz właściwą wersję protokołu SNMP
   - Wprowadź dane community dla dostępu SNMP
   - Po dodaniu OLT, pobierz listę dostępnych portów (spowoduje to również wyświetlenie poziomów sygnałów wkładek w kartach)

2. Zarządzanie modelami ONT
   - Dodaj modele ONT w systemie
   - Dla urządzeń XGSPON zaznacz odpowiednią opcję

3. Wykrywanie i konfiguracja ONT
   - W menu głównym wybierz opcję "Wykryj ONT"
   - System wyświetli listę urządzeń:
     a) Nieskonfigurowane (oznaczone jako ID = 0)
     b) Skonfigurowane nie dodane do LMS: aktywne (czarna czcionka) i nieaktywne (szara czcionka)
   - w konfiguracji pluginu istnieje możliwość wyłączenia wykrywania skonfigurowanych ont, parametr **gpon-nokia.detect_configured_onus** ustawiamy na 0

4. Dodawanie nowego ONT
   - Kliknij przycisk "+" po prawej stronie wybranego urządzenia
   - Wybierz odpowiedni model ONT z dostępnej listy
   - Przypisz profil usług odpowiedni dla klienta
   - Wybierz profil QoS

5. Dodawanie istniejącego ONT do LMS
   - Możliwe jest dodanie ONT już skonfigurowanego na OLT
   - W tym przypadku nie następuje konfiguracja urządzenia, a jedynie rejestracja w systemie LMS


Uwaga: Profile usług zawierają ustawienia, które zostaną wysłane do ONT. Pliki konfiguracyjne profili znajdują się w katalogu "gponserviceprofiles".

===========================================================
Statystyki poziomu sygnału (skrypt bin/gponsignalrrd.php):

1. Zainstalować pakiet narzędziowy **rrdtool**.
2. Ustawieniem konfiguracyjnym **gpon-nokia.rrdtool_binary** wskazać ścieżkę programu rrdtool (domyślnie _/usr/bin/rrdtool_).
3. Skonfigurować cykliczne uruchamianie skryptu co godzinę np. z cron.
4. Wykresy powinny pokazywać poziom sygnału co najmniej 2h po skonfigurowaniu skryptu.
5. Częstość próbkowania sygnału powinna zostać wskazana ustawieniem **gpon-nokia.stat_freq** (domyślnie: _3600_ [s]) i powinna być zgodna z częstością uruchamiania zadania cron skonfigurowanego w punkcie 3.

===========================================================
Autoprovisioning (skrypt bin/gponautoscript.php)

1. ONT musi być dodany do bazy LMS z flagą 'Auto provisioning'.
2. ONT musi być przypisany do klienta.
3. Do ONT musi być przypisany profil usług.

===========================================================

CHANGELOG

## [1.0.6] - 2025-06-
### Dodano
- po zmianie profilu usług (serviceprofile) ponowna konfiguracja ONT
- po zmianie profilu qos (gponoltprofilesid) ponowna konfiguracja ONT

## [1.0.5] - 2025-06-04
### Dodano
- możliwość konfiguracji veip
### Naprawiono
- poprawiono komunikację po snmp v3 

## [1.0.4] - 2025-05-28
### Dodano
- wyświetlanie adresu ip przypisanego do iphost na ont
- mozliwość konfiguracji iphost na ONT

## [1.0.3] - 2025-05-21
### Dodano
- Uptime ONT 
- Software Planned Version - upgrade ONT do planowanej wersji przy dodawaniu ONT
### Naprawiono
- dodawanie opisu portu przy dodawaniu nowego ONT

## [1.0.2] - 2025-05-20
### Naprawiono
- funkcji get_min_free był błąd i zawsze zwracała 1
- AJAX plugins - błąd z redeklaracją funkcji

## [1.0.1] - 2025-05-19
### Naprawiono
- gponsignalrrd.php nie odczytywało sygnałów z powodu błędego oida



===========================================================

TODO



