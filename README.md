Monitor bytů

Tato aplikace sbírá data z různých webů a interpretuje je sem.
Doplňuje speciální filtry, počítá vzdálenosti a pracuje s daty.

Je veřejně dostupná na odkaze http://monitorbytu.eu/

Použité technologie, knihovny a další:
- Smarty - šablonovací jazyk
- PHP
- Javascript 
- Mapy.cz API - Pro výpočty vzdálenost od metra a centra
- Composer
- Webhook  - pro odesílání notifikací o nových bytech na discord 
- Na straně serveru také běží CRON, který dělá každé 4 hodiny postupný sběr dat


Použité návrhové vzory:
- Chain
- Interface 
- Factory
- Dependency Injector 

Konfiguraci řeší .env soubory

Aplikace vznikla jako pomůcka pro mě, jelikož se chystám brzo stěhovat do Prahy. Zároveň je to ale skvělý projekt pro portfolio.

Obsahuje také 2 barevné módy
- Light - laděn do modro-bíleho vzhledu
- Dark  - laděn do černo-šedivého vzhledu
