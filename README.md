# WATRA

Platforma do organizacji wydarzeń (na start Kraków, docelowo wiele miast). Portal do listowania wydarzeń, zapisów, zarządzania katalogiem przez administrację. Zbudowana na **Sylius 2.x** (Symfony 7 + API Platform 4 + Bootstrap + Symfony UX).

> **Status:** w fazie planowania. Kodzenie jeszcze nie rozpoczęte. Pełen plan: [docs/PLAN.md](docs/PLAN.md). Roadmapa MVP w taskach: [docs/TASKS.md](docs/TASKS.md).

## Dokumentacja

- [docs/PLAN.md](docs/PLAN.md) — pełen plan architektury i bootstrapu MVP
- [docs/TASKS.md](docs/TASKS.md) — szczegółowy rozpis na ~85 atomowych tasków, 14 faz
- [docs/BACKLOG.md](docs/BACKLOG.md) — funkcje poza MVP (płatności, faktury, marketplace, etc.)
- [docs/GLOSSARY.md](docs/GLOSSARY.md) — mapa nazw Sylius ↔ domena WATRY (`Product` = Wydarzenie, `Order` = Booking, ...)
- [docs/RBAC.md](docs/RBAC.md) — system uprawnień (placeholder, pełna dokumentacja po implementacji)

## Quickstart (po Fazie 1 bootstrapu)

```bash
make up        # postaw kontenery (php, nginx, postgres, mailpit, redis)
make install   # composer install
make migrate   # migracje DB
make fixtures  # załaduj testowe dane
```

Otwórz `http://localhost` (shop) lub `http://localhost/admin` (admin).

## Emaile transakcyjne

Wszystkie maile (rejestracja, weryfikacja, reset hasła, potwierdzenie i anulowanie rezerwacji) są wysyłane **asynchronicznie** przez Symfony Messenger (transport: `doctrine`).

### Mailpit — podgląd maili (dev)

Otwórz w przeglądarce: **http://localhost:8025**

### Worker Messenger

Worker musi działać, żeby maile były faktycznie wysyłane. Uruchom go w osobnym terminalu:

```bash
sudo docker compose -f compose.yaml exec php bin/console messenger:consume async --limit=50
```

Lub w tle (kontener nie blokuje terminala):

```bash
sudo docker compose -f compose.yaml exec -d php bin/console messenger:consume async
```

W produkcji zaleca się supervisord lub systemd do zarządzania workerem.

## Wymagania

Zweryfikuj środowisko skryptem:

```bash
bash bin/check-env.sh
```

### Wymagane narzędzia i instalacja na Ubuntu/Debian

#### PHP 8.3+

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm \
  php8.3-pdo php8.3-pgsql php8.3-intl php8.3-gd \
  php8.3-zip php8.3-opcache php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-tokenizer
```

Weryfikacja: `php -v` (oczekiwane: PHP 8.3.x)

#### Composer 2.7+

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Weryfikacja: `composer --version`

#### Symfony CLI

```bash
curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash
sudo apt install -y symfony-cli
```

Weryfikacja: `symfony version`

#### Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Weryfikacja: `node --version` (oczekiwane: v20.x.x)

#### Docker + Docker Compose

```bash
# Usuń stare wersje
sudo apt remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true

# Dodaj oficjalne repo Docker
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Uruchom bez sudo
sudo usermod -aG docker $USER
newgrp docker
```

Weryfikacja: `docker run hello-world` + `docker compose version`

> **Uwaga:** po `usermod` może być wymagany wylogowanie i ponowne logowanie żeby `docker` działał bez `sudo`.

## Stack

- **Backend:** Sylius 2.x (Symfony 7.x, PHP 8.3+, Doctrine 3, API Platform 4)
- **Frontend:** Bootstrap 5.3 + Twig Hooks + Symfony UX (Turbo, Live Components, Stimulus)
- **DB:** PostgreSQL 16
- **Mail (dev):** Mailpit
- **Multi-locale:** PL (default) + EN

## Słowniczek (skrót)

W kodzie używamy nazewnictwa Sylius'a. W UI i dokumentacji — domeny WATRY:

| Sylius (kod) | WATRA (UI) |
|---|---|
| `Product` | Wydarzenie |
| `ProductVariant` | Termin |
| `Order` | Rezerwacja / Booking |
| `Customer` | Uczestnik |
| `Taxon` | Tag |

Pełna mapa: [docs/GLOSSARY.md](docs/GLOSSARY.md).
