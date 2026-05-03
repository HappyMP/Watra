# WATRA — backlog (poza MVP)

Funkcje świadomie odłożone poza MVP. Każda pozycja ma krótkie uzasadnienie i hint, jak ją zaadresujemy.

---

## E-commerce / monetyzacja

### Płatności (P24 / Stripe)
**Po MVP, faza 2.** `PaymentMethod` flow w Sylius'ie już mamy gotowy (faza 7). Dorzucenie konkretnego gatewaya = instalacja pluginu + konfiguracja credentials.
- **P24 / PayU**: `commerceweavers/sylius-tpay-plugin` lub `BitBag/SyliusPayUPlugin`
- **Stripe**: `setono/sylius-stripe-plugin` lub własna integracja
- **Czas:** ~1 dzień per provider.

### Faktury
- `sylius/invoicing-plugin` — automatyczne faktury PDF do orderów
- Integracja z fakturownią lub systemem Twojego księgowego

### Refundy
- `sylius/refund-plugin` — workflow zwrotu pieniędzy + powiadomienia
- Wymaga skonfigurowanego payment gatewaya

### Promocje, kupony
- Sylius natywnie wspiera (`sylius_promotion`, `sylius_promotion_coupon`)
- Włączamy gdy będzie potrzeba: kody znajomego, early bird discount, kody dla partnerów

---

## Komunikacja z uczestnikami

### Przypomnienia o wydarzeniach
- Cron + Symfony Messenger Schedule (od Symfony 6.4)
- Mail "Twoje wydarzenie jutro o 18:00" + opcjonalnie SMS
- Konfigurowalne przez usera (24h przed, 1h przed)

### Push notifications (web push)
- Symfony Notifier + WebPush channel
- Wymaga klucza VAPID, opt-in flow, service worker

### SMS notifications
- Symfony Notifier + Twilio/SMSAPI channel
- Use cases: weryfikacja telefonu, ostrzeżenia o anulacji

### Recenzje wydarzeń
- `sylius/review-plugin` LUB custom encja `Review`
- Tylko dla orderów ze statusem `fulfilled` i wydarzeń `COMPLETED`
- Moderacja w admin (RBAC permission `review:moderate`)

---

## Skalowanie biznesowe

### Multi-organizer (marketplace)
- `Channel` per organizator (lub `Vendor` z Sylius Plus / community plugin)
- RBAC scope: organizator widzi tylko swoje wydarzenia
- Rozliczenia per organizator (Sylius'owy `OrderItem.unitPrice` minus prowizja platformy)

### Multi-tenant / białe etykiety
- `Channel` per brand (różne hostnames, themes, treści)
- Wymaga rozszerzenia naszego RBAC o channel-scope
- Sylius natywnie wspiera multi-channel

### QR ticket / check-in app
- Generowanie QR code dla `OrderItem` (jeden bilet = jeden QR)
- PDF z biletami w mailu potwierdzającym
- Mobilna apka admin / endpoint `/api/v2/admin/check-in/{qr}` do skanowania
- Statusy: `purchased` → `checked_in` → `no_show`

---

## Frontend / SEO

### Rozbudowane SEO
- Dynamiczny `sitemap.xml` (generowany on-the-fly)
- `hreflang` per locale dla wszystkich stron
- JSON-LD `Event` schema.org beyond Sylius defaults (organizer, performer, offers, eventStatus)
- OpenGraph images per wydarzenie (auto-generated z Twig template)

### Mobile app
- React Native lub Flutter, używa naszego API Platform jako backendu
- Push notifications na poziomie OS

---

## Engineering

### Pełen E2E z Behat
- Bazowy mamy w fazie 13. Tu rozszerzamy o:
- Wszystkie scenariusze RBAC (każda rola)
- Edge cases bookingu (wyprzedanie ostatniego miejsca w wyścigu, double-booking attempt)
- Multi-locale flow (PL → EN → PL)

### Performance / cache
- Varnish lub Symfony HTTP cache na shop pages
- Redis cache dla query Doctrine (Sylius natywnie wspiera)
- Lazy loading zdjęć (LiipImagine + LQIP)

### Monitoring i obserwowalność
- Sentry dla błędów backendu
- Plausible / Matomo zamiast GA
- Prometheus exporter dla Symfony Messenger queue depth

### CI/CD
- GitHub Actions: phpstan + phpunit + behat na PR
- Auto-deploy na staging po merge do `main`
- Smoke testy E2E na staging po deployu

---

## Compliance / RODO (GDPR)

### Cookie consent
- Twig component / lekki JS banner z opt-in dla cookies
- Kategorie: niezbędne (zawsze on), analityczne (opt-in), marketingowe (opt-in)
- Integracja z Plausible/Matomo (po opt-in)

### Eksport danych usera (art. 20 RODO — prawo do przenoszenia danych)
- Endpoint `/account/export-data` zwracający JSON / ZIP z wszystkimi danymi customer'a
- Zawiera: profil, orders, interests, audit log
- Async przez Messenger (mail z linkiem do pobrania, ważnym 24h)

### Right to erasure (art. 17 RODO — prawo do bycia zapomnianym)
- Endpoint `/account/delete-account` z 7-dniowym soft-delete (cooldown na undo)
- Anonymizacja zamiast hard-delete dla customer'ów z historycznymi orderami (księgowość)
- Audit log akcji erasure (kto, kiedy, jaki user_id)

### Polityka prywatności + regulamin
- Wersjonowanie (każda zmiana = nowa wersja, customer akceptuje przy rejestracji i przy update'cie)
- Tabela `consent_log` (user_id, version, accepted_at, ip)

---

## Security hardening

### Rate limiting (rozszerzenia poza MVP)
- `symfony/rate-limiter` na endpointach: `/login` (5/min/IP), `/register` (3/min/IP), `/reset-password` (3/min/IP), API booking POST (10/min/customer)
- W MVP mamy bundle zainstalowany, ale konfiguracja per-route — w backlogu

### CAPTCHA / anti-bot
- hCaptcha lub Turnstile na rejestracji i reset-password
- Honeypot field jako pierwsza linia obrony

### 2FA dla admin'ów
- `scheb/2fa-bundle` — TOTP (Google Authenticator)
- Wymuszone dla ról z `isSuperAdmin` lub permission `admin_user:edit`

---

## Inne pomysły (parking lot)

- Newsletter z polecanymi wydarzeniami (Mautic / Mailcoach integracja)
- "Zaproś znajomego" (referral codes z bonusem)
- Wydarzenia hybrydowe (online + offline) — `ProductVariant` ma 2 typy (online stream + offline ticket)
- Lista oczekujących (waitlist) gdy wydarzenie pełne
- Subskrypcje ("członkostwo WATRA" — miesięczny abonament + dostęp do zamkniętych wydarzeń)
- Integracja z Google Calendar / Outlook (eksport `.ics`)
- Galeria fotograficzna z wydarzeń po fakcie
- Forum / dyskusja per wydarzenie (przed i po)
