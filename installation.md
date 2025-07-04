# Installation

## Installation with automatic ACME (letsencrypt cert) by Traefik
```bash
git clone https://github.com/XAKEPEHOK/lokilizer.git
cd lokilizer
cp .env.traefik .env

# Edit .env end fill following env: 
# - PROJECT_DOMAIN=you-domain.com
# - LETSENCRYPT_EMAIL=you-real-email@not-example.com 
# - SIGN_SECRET=any_secret_for_encryption_your_backup
nano .env
docker compose up -d
```

## Installation with simply 8081 port forwarding
```bash
git clone https://github.com/XAKEPEHOK/lokilizer.git
cd lokilizer
cp .env.prod .env

# Edit .env end fill following env: 
# - PROJECT_DOMAIN=you-domain.com
# - SIGN_SECRET=any_secret_for_encryption_your_backup
nano .env
docker compose up -d
```

## How to update
```bash
cd lokilizer
docker compose stop
git pull
docker compose up -d
```