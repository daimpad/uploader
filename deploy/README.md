# Deployment auf den Netcup-Server

Nach jedem Merge auf `main` stößt GitHub Actions per SSH das Skript
`deploy/deploy.sh` auf dem Server an. Der Server holt sich den Stand dann
selbst per `git`. Gebaut wird nichts bei GitHub, es gibt keinen Artefakt-Upload.

```
Merge auf main
   └─> .github/workflows/deploy-netcup.yml
          └─> ssh netcup "bash ./deploy/deploy.sh"
                 ├─ git fetch + reset --hard origin/main
                 ├─ composer install --no-dev
                 ├─ Syntaxprüfung über alle PHP-Dateien
                 ├─ PHP neu laden (Opcache)
                 └─ Health-Check
                      └─ schlägt etwas fehl: zurück auf den vorherigen Commit
```

## Was ein Deployment nicht anfasst

`git reset --hard` fasst ignorierte Pfade nicht an. Erhalten bleiben:

| Pfad | Inhalt |
| --- | --- |
| `.env` | Zugangsdaten, liegt bewusst nicht im Repository |
| `uploads/` | die hochgeladenen Dateien |
| `tmp/` | angefangene Chunk-Uploads |
| `vendor/` | wird bei Bedarf neu installiert |

Alles andere wird auf den Stand von `origin/main` gesetzt. **Lokale Änderungen
am Code auf dem Server gehen dabei verloren** — das ist gewollt, der Server ist
ein Abbild des Repositories und keine zweite Arbeitskopie.

---

## Einmalige Einrichtung

### 1. Repository auf den Server holen

```bash
ssh dein-user@dein-server.netcup.net
cd /var/www/vhosts/deine-domain          # Pfad je nach Netcup-Produkt
git clone https://github.com/daimpad/uploadez.git .
```

Klont man in ein bereits belegtes Verzeichnis, meckert git. Dann stattdessen:

```bash
git init
git remote add origin https://github.com/daimpad/uploadez.git
git fetch origin main
git checkout -B main origin/main
```

### 2. Konfiguration anlegen

```bash
cp .env.example .env
nano .env        # DB-Zugang, APP_URL, SMTP, ADMIN_PASSWORD_HASH
chmod 600 .env   # enthält Passwörter
```

Admin-Passwort-Hash erzeugen:

```bash
php -r "echo password_hash('deinPasswort', PASSWORD_BCRYPT), PHP_EOL;"
```

### 3. Datenbank anlegen

```bash
mysql -u root -p < schema.sql
```

Legt Datenbank und beide Tabellen an (`files`, `rate_limits`). Das Skript ist
mit `CREATE ... IF NOT EXISTS` geschrieben und darf wiederholt laufen.

### 4. Verzeichnisse und Abhängigkeiten

```bash
mkdir -p uploads tmp && chmod 750 uploads tmp
composer install --no-dev --optimize-autoloader
chmod +x deploy/deploy.sh
```

Der Webserver-Nutzer muss in `uploads/` und `tmp/` schreiben dürfen. Läuft PHP
unter einem anderen Nutzer als dem, der deployt, gehört die Gruppe angepasst:

```bash
chgrp www-data uploads tmp && chmod 770 uploads tmp
```

### 5. Ersten Lauf von Hand testen

```bash
bash ./deploy/deploy.sh
```

Erst wenn das durchläuft, den Automatismus scharf schalten.

---

## Schlüssel und Secrets

### Deploy-Schlüssel erzeugen

Auf einem Rechner, dem du traust — nicht auf dem Server:

```bash
ssh-keygen -t ed25519 -C "github-actions-uploadez" -f ~/.ssh/uploadez_deploy -N ""
```

Öffentlichen Teil auf den Server legen:

```bash
ssh-copy-id -i ~/.ssh/uploadez_deploy.pub dein-user@dein-server.netcup.net
```

### Den Schlüssel einsperren (empfohlen)

Ohne weitere Maßnahme darf dieser Schlüssel alles, was dein Benutzer darf. Mit
einem festen Befehl in `~/.ssh/authorized_keys` auf dem Server darf er nur noch
genau eine Sache — auch wenn er GitHub abhandenkommt:

```
command="cd /var/www/vhosts/deine-domain && bash ./deploy/deploy.sh",no-agent-forwarding,no-port-forwarding,no-pty,no-X11-forwarding ssh-ed25519 AAAA… github-actions-uploadez
```

Der Workflow schickt zwar einen Befehl mit, dieser wird dann aber ignoriert und
der feste ausgeführt.

### Fingerabdruck des Servers holen

Damit die Verbindung nicht blind auf `StrictHostKeyChecking=no` läuft:

```bash
ssh-keyscan -p 22 dein-server.netcup.net 2>/dev/null
```

Die Ausgabe kommt vollständig ins Secret `NETCUP_KNOWN_HOSTS`.

### Secrets in GitHub hinterlegen

**Settings › Secrets and variables › Actions › Secrets**

| Name | Inhalt |
| --- | --- |
| `NETCUP_SSH_KEY` | Inhalt von `~/.ssh/uploadez_deploy`, der **private** Schlüssel, komplett mit Kopf- und Fußzeile |
| `NETCUP_HOST` | `dein-server.netcup.net` |
| `NETCUP_USER` | SSH-Benutzer |
| `NETCUP_PATH` | absoluter Pfad zum Projekt auf dem Server |
| `NETCUP_KNOWN_HOSTS` | Ausgabe von `ssh-keyscan` |

**Settings › Secrets and variables › Actions › Variables** (optional)

| Name | Vorgabe | Wofür |
| --- | --- | --- |
| `NETCUP_SSH_PORT` | `22` | abweichender SSH-Port |
| `NETCUP_DEPLOY_BRANCH` | `main` | anderer Branch |
| `NETCUP_RELOAD_CMD` | leer | Befehl zum Neuladen von PHP-FPM, siehe unten |
| `NETCUP_HEALTH_URL` | leer | `https://deine-domain/health.php` |

---

## Opcache

Ist Opcache aktiv — auf Produktionsservern der Normalfall — liefert PHP nach
einem Deployment weiter den alten Code aus, bis die Prüfintervalle ablaufen.
`NETCUP_RELOAD_CMD` behebt das.

**vServer mit root:**

```
sudo systemctl reload php8.3-fpm
```

Damit das ohne Passwort geht, auf dem Server per `visudo` eintragen:

```
dein-user ALL=(root) NOPASSWD: /bin/systemctl reload php8.3-fpm
```

**Webhosting ohne root:** kein `systemctl` verfügbar. Entweder in der
`.user.ini` `opcache.validate_timestamps=1` mit kurzem
`opcache.revalidate_freq` setzen, oder die Variable leer lassen und die kurze
Verzögerung hinnehmen. Das Skript weist im Log darauf hin.

---

## Was das Skript selbst prüft

- **`.env` fehlt** → Abbruch, bevor irgendetwas verändert wird
- **`composer install` schlägt fehl** → zurück auf den vorherigen Commit
- **Syntaxfehler in einer PHP-Datei** → zurück auf den vorherigen Commit
- **Health-Check ungleich HTTP 200** → zurück auf den vorherigen Commit

Der Rückweg setzt nur den Code zurück. Eine Datenänderung nimmt er nicht
zurück — `schema.sql` ist additiv, ein Rollback über Migrationen gibt es nicht.

Das Skript liegt selbst im Repository und wird beim Deployment mit ersetzt.
Damit das nicht mitten im Lauf umkippt, steht der gesamte Ablauf in einer
Funktion: Bash liest die vollständig ein, bevor der erste Befehl darin läuft.

---

## Ohne GitHub Actions

Wer keinen SSH-Schlüssel bei GitHub hinterlegen will, kann dasselbe Skript per
Cron aufrufen:

```cron
*/5 * * * * cd /var/www/vhosts/deine-domain && bash ./deploy/deploy.sh >> /var/log/uploadez-deploy.log 2>&1
```

Läuft ins Leere, wenn sich nichts geändert hat — die erste Prüfung vergleicht
den lokalen mit dem entfernten Commit und bricht dann ab.

---

## Fehlersuche

| Symptom | Ursache |
| --- | --- |
| `Host key verification failed` | `NETCUP_KNOWN_HOSTS` fehlt oder passt nicht zum Port |
| `Permission denied (publickey)` | öffentlicher Schlüssel nicht in `authorized_keys`, oder Rechte auf `~/.ssh` zu offen (muss `700`, `authorized_keys` `600`) |
| `.env fehlt` | Schritt 2 der Einrichtung nachholen |
| `ist kein Git-Arbeitsverzeichnis` | Schritt 1 nachholen, oder `NETCUP_PATH` zeigt woandershin |
| Deployment grün, Seite zeigt alten Stand | Opcache, siehe oben |
| `composer: command not found` | Composer fehlt; die App fällt dann auf `mail()` statt PHPMailer zurück |
