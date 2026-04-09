# Session Migration Demo

A Laravel demo app that demonstrates how to migrate encrypted Redis sessions between servers (or Redis vendors) without logging users out.

## Overview

This app uses Laravel Fortify for authentication with encrypted sessions stored in Redis. It provides a dashboard that displays session data in plain text, proving that encrypted session data can be read from Redis and decrypted by Laravel using the `APP_KEY`.

### Key Configuration

| Setting | Value |
|---------|-------|
| Session Driver | `redis` |
| Session Encryption | `true` |
| Session Serialization | `json` |
| Redis Client | `phpredis` |

## Local Setup

```bash
composer install
bun install && bun run build
cp .env.example .env   # then configure DB and Redis
php artisan key:generate
php artisan migrate
```

If using Laravel Herd, the app is available at `https://session-migration.test`.

## Usage

1. Visit `/register` to create a user
2. After login, the dashboard shows all session data as decrypted plain text
3. Add custom key/value pairs to the session via the form
4. Visit `/session/raw` to see the encrypted Redis blob alongside the decrypted output

## Session Migration Guide

### Prerequisites

- The **source** server is running this app with sessions in Redis
- The **destination** server has the app deployed and ready to receive traffic
- Both servers must share the same `APP_KEY` (found in `.env`)

### Step 1: Copy the APP_KEY

The `APP_KEY` is used to encrypt and decrypt session data. The destination server **must** have the exact same key.

```bash
# On the source server
grep APP_KEY .env
# APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=

# Copy this value to the destination server's .env
```

> If the keys don't match, Laravel on the new server won't be able to decrypt existing sessions and all users will be logged out.

### Step 2: Export Redis Data

Choose one of these methods depending on your setup:

#### Option A: RDB Snapshot (full Redis migration)

```bash
# On the source Redis server
redis-cli BGSAVE
# Wait for the snapshot to complete
redis-cli LASTSAVE

# Copy the dump file to the destination
scp /var/lib/redis/dump.rdb destination-server:/var/lib/redis/dump.rdb

# Restart Redis on the destination to load the snapshot
ssh destination-server 'sudo systemctl restart redis'
```

#### Option B: DUMP/RESTORE (selective key migration)

Useful when you only want to migrate session keys, not the entire Redis database.

```bash
# Export all session keys from source
redis-cli --scan --pattern 'session-migration-database-*' | while read key; do
    ttl=$(redis-cli TTL "$key")
    dump=$(redis-cli DUMP "$key")
    # RESTORE on destination (adjust host/port)
    redis-cli -h destination-host RESTORE "$key" "$ttl" "$dump" REPLACE
done
```

#### Option C: Redis replication (zero-downtime)

Set up the destination Redis as a replica of the source, then promote it to primary after the DNS switch.

```bash
# On the destination Redis
redis-cli REPLICAOF source-host 6379

# After DNS cutover, promote to primary
redis-cli REPLICAOF NO ONE
```

### Step 3: Verify Session Config

Ensure the destination server's `.env` and config match the source:

```env
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1       # or new Redis host
REDIS_PORT=6379
```

Also verify that `config/session.php` has `'serialization' => 'json'` (or matches the source).

### Step 4: Switch Traffic

Update DNS or your load balancer to point to the destination server. Users' session cookies contain a session ID that maps to a Redis key — as long as:

1. The Redis key exists on the new server
2. The `APP_KEY` matches
3. The session config matches

...users remain logged in seamlessly.

### Step 5: Verify

1. Log in on the source server and add some custom session data
2. Switch to the destination server
3. Visit `/dashboard` — your session data should appear
4. Visit `/session/raw` — the encrypted Redis blob should decrypt correctly

## Changing Redis Vendors

The same process applies when migrating between Redis vendors (e.g., from self-hosted Redis to AWS ElastiCache, Upstash, or Redis Cloud):

1. Export data from the current Redis instance
2. Import into the new vendor's Redis instance
3. Update `REDIS_HOST`, `REDIS_PORT`, and `REDIS_PASSWORD` in `.env`
4. Verify the Redis key prefix matches (`session-migration-database-` by default, set via `REDIS_PREFIX`)
5. Deploy — sessions persist because the data and `APP_KEY` are unchanged

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| All users logged out after migration | `APP_KEY` mismatch | Copy the exact `APP_KEY` from the source `.env` |
| Session data exists but can't decrypt | `SESSION_ENCRYPT` was off on source, on at destination (or vice versa) | Ensure both servers have the same `SESSION_ENCRYPT` value |
| Redis keys not found | Key prefix mismatch | Check `REDIS_PREFIX` in `.env` and `config/database.php` — default is `{app-name}-database-` |
| Session cookie not sent to new server | Domain mismatch | Ensure `SESSION_DOMAIN` matches or is `null` for same-domain |
| Serialization error | Serialization format mismatch | Both servers must use the same `session.serialization` value (`json` or `php`) |
