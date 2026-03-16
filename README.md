# Conway's Game of Life

Simulace Conway's Game of Life — PHP/Symfony backend + React/TypeScript frontend v Dockeru.

## Tech Stack

- PHP 8.3 + Symfony 7.4
- React 19 + TypeScript + Vite
- PostgreSQL 16 + Redis 7
- Docker

## Spuštění

```bash
# 1. Nastartovat Docker kontejnery
make up

# 2. Nainstalovat backend závislosti
make install

# 3. Spustit databázové migrace
make migrate

# 4. Nainstalovat frontend závislosti
make front-install
```

Aplikace běží na [http://localhost](http://localhost), frontend dev server na [http://localhost:5173](http://localhost:5173).

## Make příkazy

### Docker

| Příkaz | Popis |
|--------|-------|
| `make up` | Spustit kontejnery |
| `make down` | Zastavit kontejnery |
| `make build` | Sestavit a spustit kontejnery |
| `make shell` | Shell do PHP kontejneru |
| `make logs` | Sledovat logy |

### Backend

| Příkaz | Popis |
|--------|-------|
| `make install` | Composer install |
| `make test` | Spustit testy |
| `make phpstan` | Statická analýza |
| `make ecs` | Kontrola code style |
| `make ecs-fix` | Automatická oprava code style |
| `make rector` | Rector (dry-run) |
| `make rector-fix` | Rector (aplikovat změny) |
| `make migrate` | Spustit migrace |
| `make simulate` | Spustit simulaci z fixture |

### Frontend

| Příkaz | Popis |
|--------|-------|
| `make front-install` | npm install |
| `make front-dev` | Dev server |
| `make front-build` | Produkční build |

### Kombinované

| Příkaz | Popis |
|--------|-------|
| `make check` | ECS + PHPStan + testy |
| `make fix` | ECS fix + Rector fix |
