Implementační dokumentace k 1. úloze do IPP 2023/2024\
Jméno a příjmení: Ján Findra\
Login: xfindr01

## Implementácia

### Kontrola argumentov
Program začína kontrolou argumentov, kde je povolené 0 alebo práve jeden argument. Povolený argument môže mať dva tvary `-h` alebo `--help`. Argument slúži na vypísanie pomôcky.

### Načítanie vstupu
Ďalej sa načíta vstup zo štandardného vstupu, na ten sa zavolá funkcia `remove_comments`, ktorá odstráni komentáre a prázdne riadky. Následne sa vstup rozdelí podľa znaku nového riadku `\n`.

### Kontrola identifikátora jazyka
Na prvom riadku sa musí nachádzať `.IPPcode24`. Implementoval som aj možnosť, že sa pred ním nachádza nejaký počet medzier.

### Spracovanie inštrukcií
Zvyšné riadky sa spracujú pomocou for cyklu prechádzajúceho cez všetky riadky vstupu. Najprv sa odstránia opakujúce sa medzery a reťazec sa rozdelí podľa medzier. Prvý prvok nového zoznamu sa zmení na veľké písmo, kvôli následnej kontrole. Zoznam povolených inštrukcií je v slovníku `instructions`, kde sa nachádza názov inštrukcie a jej parametre. Ak inštrukcia existuje, tak sa vytvorí nový podprvok. Následne sa zavolá funkcia `check_operand`, ktorá skontroluje operandy pre danú inštrukciu.\
Funkcia `check_operand` zistí zo slovníka inštrukcií, aká typy argumentov sú očakávané a následne ich skontroluje. Ak je typ jasne definovaný, tak kontrola je vcelku jednoduchá. Pre kontrolu som použil regex-y. Komplikované bolo skontrolovať správne použitie `\`. Vyriešil som to spôsobom, že nájdem výskyt `\` a následne skontrolujem, že či na nasledujúcich 3 pozíciach sú čísla. Ďalšou komplikáciou bola kontrola integer-u, keďže môže byť definovaný viacero spôsobmi (dekadicky, hexadecimálne, ...). Vyriešil som to komplexnejším regex-om. Typ `Symb` sa vyhodnotí pomocou kontroly základných dátových typov. Funkcia vracia typ operandu a jeho hodnotu.\
Ak všetko prebehlo v poriadku, tak sa vytvorí nový podprvok `arg` so získanými hodnotami.

### Úprava výstupu a výpis
Na konci programu sa zavolá funkcia `arrange_xml`, ktorá dodá výslednému xml súboru správnu štruktúru. Tento sa následne vypíše na štandardný výstup.