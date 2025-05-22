# Claude Chat Pro - WordPress Plugin

Zaawansowana wtyczka WordPress do komunikacji z Claude AI z integracją GitHub.

## Funkcje

- 💬 **Czat z Claude AI** - Pełna integracja z najnowszymi modelami Claude
- 📁 **Załączniki plików** - Możliwość załączania plików tekstowych do analizy
- 🐙 **Integracja GitHub** - Dostęp do repozytoriów i plików kodu
- 📊 **Historia rozmów** - Automatyczne zapisywanie i przeglądanie historii
- 🔒 **Bezpieczeństwo** - Szyfrowanie kluczy API i zabezpieczenia
- 🎨 **Responsywny interfejs** - Dostosowany do różnych urządzeń
- 📈 **Diagnostyka** - Narzędzia do monitorowania i rozwiązywania problemów

## Wymagania

- WordPress 6.0+
- PHP 7.4+
- Rozszerzenie cURL
- Rozszerzenie OpenSSL (opcjonalne, dla szyfrowania)

## Instalacja

1. Pobierz wtyczkę i rozpakuj do katalogu `/wp-content/plugins/claude-chat-pro/`
2. Aktywuj wtyczkę w panelu administracyjnym WordPress
3. Przejdź do **Claude Chat > Ustawienia**
4. Skonfiguruj klucz API Claude i opcjonalnie token GitHub

## Konfiguracja

### Klucz API Claude

1. Zarejestruj się na [console.anthropic.com](https://console.anthropic.com/)
2. Przejdź do sekcji API Keys
3. Utwórz nowy klucz API
4. Skopiuj klucz do ustawień wtyczki

### Token GitHub (opcjonalnie)

1. Przejdź do [github.com/settings/tokens](https://github.com/settings/tokens)
2. Kliknij "Generate new token (classic)"
3. Nadaj nazwę tokenowi
4. Wybierz uprawnienia: `repo`, `user`
5. Kliknij "Generate token"
6. Skopiuj token do ustawień wtyczki

## Użytkowanie

### Podstawowy czat

1. Przejdź do **Claude Chat** w menu administracyjnym
2. Wpisz wiadomość w polu tekstowym
3. Naciśnij Enter lub kliknij przycisk wysyłania
4. Poczekaj na odpowiedź Claude AI

### Załączanie plików

1. Kliknij przycisk "Załącz plik"
2. Wybierz pliki tekstowe (max 1MB każdy)
3. Pliki zostaną przeanalizowane przez Claude AI

### Praca z kodem z GitHub

1. Skonfiguruj token GitHub w ustawieniach
2. Repozytoria pojawią się w panelu bocznym
3. Przeglądaj i wybieraj pliki do analizy

### Historia rozmów

- Automatyczne zapisywanie wszystkich rozmów
- Przeszukiwanie historii
- Eksport do CSV
- Filtrowanie po dacie

## Modele Claude

Wtyczka obsługuje najnowsze modele Claude:

- **Claude 3.5 Sonnet** - Najnowszy i najbardziej zaawansowany
- **Claude 3.5 Haiku** - Szybki i ekonomiczny
- **Claude 3 Opus** - Najbardziej inteligentny
- **Claude 3 Sonnet** - Zbalansowany
- **Claude 3 Haiku** - Podstawowy

## Bezpieczeństwo

- Klucze API są szyfrowane w bazie danych
- Weryfikacja nonce dla wszystkich żądań AJAX
- Sanityzacja wszystkich danych wejściowych
- Kontrola uprawnień użytkowników

## Diagnostyka

Wtyczka zawiera narzędzia diagnostyczne:

- Test połączeń API
- Sprawdzanie wymagań systemowych
- Status bazy danych
- Uprawnienia plików
- Eksport danych

## API i hooki

### Filtry

```php
// Modyfikacja maksymalnego rozmiaru pliku
add_filter('claude_chat_max_file_size', function($size) {
   return 2 * 1024 * 1024; // 2MB
});

// Modyfikacja dostępnych modeli
add_filter('claude_chat_available_models', function($models) {
   // Dodaj własny model
   return $models;
});