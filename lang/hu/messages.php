<?php

return [
    'auth' => [
        'failed' => 'Ezek a hitelesítő adatok nem egyeznek a nyilvántartásainkkal.',
        'throttle' => 'Túl sok bejelentkezési kísérlet. Kérjük, próbálja újra :seconds másodperc múlva.',
        'login_success' => 'Sikeres bejelentkezés',
        'logout_success' => 'Sikeresen kijelentkezett',
        'registration_success' => 'Sikeres regisztráció',
    ],
    'validation' => [
        'required' => 'A(z) :attribute mező kitöltése kötelező.',
        'email' => 'A(z) :attribute érvényes e-mail cím kell legyen.',
        'unique' => 'A(z) :attribute már használatban van.',
        'min' => [
            'string' => 'A(z) :attribute legalább :min karakter hosszú kell legyen.',
        ],
        'confirmed' => 'A(z) :attribute megerősítése nem egyezik.',
    ],
    'spaces' => [
        'created' => 'Létesítmény sikeresen létrehozva',
        'updated' => 'Létesítmény sikeresen frissítve',
        'deleted' => 'Létesítmény sikeresen törölve',
        'not_found' => 'Létesítmény nem található',
        'unauthorized' => 'Nincs jogosultsága ehhez a művelethez',
    ],
    'user' => [
        'updated' => 'Profil sikeresen frissítve',
        'password_updated' => 'Jelszó sikeresen frissítve',
        'password_incorrect' => 'A jelenlegi jelszó helytelen',
        'account_deleted' => 'Fiók sikeresen törölve',
    ],
];