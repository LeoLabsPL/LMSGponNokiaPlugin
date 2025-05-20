# Dodatek GPON / XGSPON NOKIA do LMS

## Instalacja:
- Zawartość paczki umieścić w katalogu plugins w LMS.
- Utworzyć dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSGponNokiaPlugin
   do katalogu ../plugins/LMSGponNokiaPlugin/img.
   W katalogu img wykonać polecenie: ln -s ../plugins/LMSGponNokiaPlugin/img LMSGponNokiaPlugin
- Włączyć wtyczkę w zakładce Konfiguracja -> Lista wtyczek
- Jeśli wtyczka nie jest widoczna wykonać polecenie w katalogu lms: composer update
