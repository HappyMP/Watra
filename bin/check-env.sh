#!/usr/bin/env bash
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0

check() {
    local name="$1"
    local actual="$2"
    local required="$3"
    local ok="$4"

    if [ "$ok" = "true" ]; then
        echo -e "  ${GREEN}✓${NC} $name: $actual"
        PASS=$((PASS + 1))
    else
        echo -e "  ${RED}✗${NC} $name: $actual (wymagane: $required)"
        FAIL=$((FAIL + 1))
    fi
}

version_gte() {
    # zwraca 0 (success) jeśli $1 >= $2
    printf '%s\n%s\n' "$2" "$1" | sort -V -C
}

echo "=== WATRA — weryfikacja środowiska ==="
echo ""

# --- PHP ---
echo "PHP:"
if command -v php &>/dev/null; then
    PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    version_gte "$PHP_VER" "8.3" && PHP_OK="true" || PHP_OK="false"
    check "Wersja" "$PHP_VER" "8.3+" "$PHP_OK"

    for EXT in pdo_pgsql intl gd zip opcache mbstring xml; do
        # opcache jest listowany jako "Zend OPcache" w php -m
        if [ "$EXT" = "opcache" ]; then
            GREP_PAT="opcache\|Zend OPcache"
        else
            GREP_PAT="^${EXT}$"
        fi
        if php -m 2>/dev/null | grep -qi "$GREP_PAT"; then
            check "Rozszerzenie $EXT" "zainstalowane" "$EXT" "true"
        else
            check "Rozszerzenie $EXT" "BRAK" "$EXT" "false"
        fi
    done
else
    echo -e "  ${RED}✗${NC} PHP: nie znaleziono"
    FAIL=$((FAIL + 1))
fi

echo ""

# --- Composer ---
echo "Composer:"
if command -v composer &>/dev/null; then
    COMP_VER=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    COMP_MAJOR=$(echo "$COMP_VER" | cut -d. -f1)
    COMP_MINOR=$(echo "$COMP_VER" | cut -d. -f2)
    if [ "$COMP_MAJOR" -ge 2 ] && [ "$COMP_MINOR" -ge 7 ] || [ "$COMP_MAJOR" -gt 2 ]; then
        COMP_OK="true"
    else
        COMP_OK="false"
    fi
    check "Wersja" "$COMP_VER" "2.7+" "$COMP_OK"
else
    echo -e "  ${RED}✗${NC} Composer: nie znaleziono"
    FAIL=$((FAIL + 1))
fi

echo ""

# --- Symfony CLI ---
echo "Symfony CLI:"
if command -v symfony &>/dev/null; then
    SYM_VER=$(symfony version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1 || echo "zainstalowany")
    check "Wersja" "${SYM_VER}" "dowolna" "true"
else
    echo -e "  ${YELLOW}⚠${NC}  Symfony CLI: nie znaleziono (opcjonalne dla dev, ale przydatne)"
fi

echo ""

# --- Node.js ---
echo "Node.js:"
if command -v node &>/dev/null; then
    NODE_VER=$(node --version | tr -d 'v')
    NODE_MAJOR=$(echo "$NODE_VER" | cut -d. -f1)
    [ "$NODE_MAJOR" -ge 20 ] && NODE_OK="true" || NODE_OK="false"
    check "Wersja" "$NODE_VER" "20+" "$NODE_OK"
else
    echo -e "  ${RED}✗${NC} Node.js: nie znaleziono"
    FAIL=$((FAIL + 1))
fi

echo ""

# --- Docker ---
echo "Docker:"
if command -v docker &>/dev/null; then
    DOCKER_VER=$(docker --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    check "Wersja" "$DOCKER_VER" "dowolna" "true"

    if docker info &>/dev/null 2>&1; then
        check "Daemon" "działa" "uruchomiony" "true"
    else
        check "Daemon" "NIE DZIAŁA (brak uprawnień lub nie uruchomiony)" "uruchomiony" "false"
    fi
else
    echo -e "  ${RED}✗${NC} Docker: nie znaleziono"
    FAIL=$((FAIL + 1))
fi

echo ""

# --- Docker Compose ---
echo "Docker Compose:"
if docker compose version &>/dev/null 2>&1; then
    DC_VER=$(docker compose version | grep -oP '\d+\.\d+\.\d+' | head -1)
    check "Wersja (plugin)" "$DC_VER" "dowolna" "true"
elif command -v docker-compose &>/dev/null; then
    DC_VER=$(docker-compose --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    check "Wersja (standalone)" "$DC_VER" "dowolna" "true"
else
    echo -e "  ${RED}✗${NC} Docker Compose: nie znaleziono"
    FAIL=$((FAIL + 1))
fi

echo ""

# --- Podsumowanie ---
echo "======================================"
TOTAL=$((PASS + FAIL))
echo -e "Wynik: ${GREEN}${PASS}/${TOTAL}${NC} OK"
if [ "$FAIL" -gt 0 ]; then
    echo -e "${RED}${FAIL} problemów do rozwiązania — patrz README.md sekcja 'Wymagania'.${NC}"
    exit 1
else
    echo -e "${GREEN}Wszystko gotowe! Można przystąpić do 'make up'.${NC}"
    exit 0
fi
