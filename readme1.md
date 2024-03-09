Implementační dokumentace k 1. úloze do IPP 2023/2024\
Jméno a příjmení: Ján Findra\
Login: xfindr01

## Implementácia

Program začína kontrolou argumentov, kde je povolené buď 0 argumentov alebo práve jeden, -h na zavolanie pomôcky.\
Ďalej sa načíta vstup zo sdtin, na ten sa zavolá funkcia `remove_comments`, ktorá odstráni komentáre a prázdne riadky. Následne sa vstup rozdelí podľa znaku nového riadku `\n`. Na prvom riadku sa musí nachádzať `.IPPcode24`. Implementoval som aj možnosť, že sa pred ním nachádza nejaký počet medzier. Zbytok programu, zvyšné riadky, sa spracujú pomocou for cyklu.\
Najprv sa odstránia opakujúce sa medzery a reťazec sa rozdelí podľa medzier. Prvý prvok nového zoznamu sa zmení na veľké písmo, kvôli následnej kontrole. Zoznam povolených inštrukcií je v slovníku, kde sa nachádza názov inštrukcie a jej parametre. Ak inštrukcia existuje, tak sa vytvorí nový podprvok. Potom sa zavolá funkcia `check_operand`, ktorá skontroluje operandy pre danú inštrukciu.\
Funkcia `check_operand` zistí zo slovníka inštrukcií, aká typy argumentov sú očakávané a to následne skontroluje. Ak je typ jasne definovaný, tak kontrola je vcelku jednoduchá. Pre kontrolu som použil regex-y. Komplikované bolo skontrolovať správne použitie `\`. Vyriešil som to spôsobom, že nájdem výskyt `\` a následne skontrolujem, že či na nasledujúcich 3 pozíciach sú čísla. Ďalšou komplikáciou bola kontrola integer-u, keďže môže byť definovaná viacero spôsobmi (dekadicku, hexadecimálne, ...). Vyriešil som to komplexnejším regex-om. Typ `Symb` sa vyhodnotí pomocou kontroly základných dátových typov. Funkcia vracia typ operanda a jeho hodnotu.\
Ak všetko prebehlo v poriadku, tak sa vytvorí nový podprvok so získanými hodnotami.\
Na konci programu sa zavolá funkcia `arrange_xml`, ktorá dodá výslednému xml súboru správnu štruktúru. Tento sa následne pošle na stdout.