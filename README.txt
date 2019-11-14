Wymagania:
PHP 7.2
Yarn
Composer
MySQL


Komendy:
composer install - zainstaluje wszystkie potrzebne pakiety danych
yarn encore dev - budowanie plików sccs(kompilacja stylów)

następnie w pliku .env musimy ustawić
APP_DOMAIN oraz DATABASE_URL=mysql://<login>@<ip>:<port>/<database>
następnie wystarczy włączyć skrypt rebuildDatabase.sh który stworzy baze danych, tabele, i wypełni ją dummy data.
Ostatnią komende którą musimy wpisać to: php bin/console server:run
jeśli dostaniemy zwrotną wiadomość: [OK] Server listening on <link>
wszystko zostało skonfigurowane poprawnie i możemy odwiedzić naszą strone.