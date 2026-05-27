# Running the dashboard on the Raspberry Pi

This guide moves the **Node web app** onto the Raspberry Pi, alongside
the **Python face scanner** that already runs there. The **MySQL
database stays on the main server** exactly as it is now — only the
web app moves.

After this, the Pi runs two services:

| Service                  | Port | What it does                         |
|---------------------------|------|--------------------------------------|
| `jondo-dashboard.service` | 3000 | The web app (clock-in, admin, API)   |
| `jondo-face.service`      | 8080 | The face scanner + camera stream     |

The database lives on the main server and the Pi connects to it over
the network.

---

## Before you start

- The Pi already runs the face scanner (`jondo-face.service`).
- MySQL stays on the main server. Remote access to it was already
  enabled earlier (bind-address, a remote DB user, and the Windows
  firewall rule for port 3306) — so the Pi can already reach it.
- Give the Pi a **fixed IP address** (a DHCP reservation on your
  router is easiest). The kiosk and admins will use this address, so
  it must not change.
- Know the main server's LAN IP — that's your `DB_HOST`.

In the steps below the Pi's IP is written as `<pi-ip>` and the main
server's IP as `<server-ip>`.

---

## Step 1 — Install Node.js on the Pi

The app needs Node 18 or newer. Install Node 20 LTS:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version    # should print v20.x (must be v18 or higher)
which node        # note this path — used in the service file
```

If `which node` is not `/usr/bin/node`, edit the `ExecStart` line in
the service file (Step 6) to match.

## Step 2 — Get the dashboard code onto the Pi

Put the whole `dashboard` folder on the Pi at:

```
/home/pi/jondo-time-clock/dashboard
```

Either clone the repository that holds the dashboard code:

```bash
cd ~
git clone <your-dashboard-repo-url> jondo-time-clock
```

…or copy the `dashboard` folder across with `scp` or a USB stick.
The scanner files in `dashboard/pi/` can stay where they already are.

## Step 3 — Install the app's dependencies

```bash
cd /home/pi/jondo-time-clock/dashboard
npm install --omit=dev
```

This takes a few minutes on a Pi 3 — that's normal.

## Step 4 — Configure the dashboard .env

Create `/home/pi/jondo-time-clock/dashboard/.env` (copy `.env.example`
if there is one) and set the database to point at the **main server**:

```ini
# Database — stays on the main server
DB_HOST=<server-ip>
DB_PORT=3306
DB_USER=timeclock
DB_PASS=your-db-password
DB_NAME=timeclock

# Web app
PORT=3000
SESSION_SECRET=pick-a-long-random-string
NODE_ENV=production

# Must match API_TOKEN in dashboard/pi/.env (the scanner)
PI_API_TOKEN=your-existing-pi-token
```

Use the **remote** MySQL user you created earlier (the one that may
connect from another host) — not a `localhost`-only account. The
main server's MySQL must accept connections from `<pi-ip>`.

## Step 5 — Apply any new database migrations

Re-running migrations is safe (already-applied ones are skipped):

```bash
cd /home/pi/jondo-time-clock/dashboard
npm run migrate
```

This makes sure the camera-schedule and WhatsApp columns exist.

## Step 6 — Test it runs

```bash
cd /home/pi/jondo-time-clock/dashboard
npm start
```

You should see `listening on http://localhost:3000`. From another
computer on the network, open `http://<pi-ip>:3000` — the clock-in
page should load. Log in to the admin side to confirm the database
connection works. Press `Ctrl+C` to stop the test.

If it can't reach the database, double-check `DB_HOST`, that MySQL is
running on the main server, and that the remote user/firewall allow
the Pi's IP.

## Step 7 — Point the scanner at the local dashboard

The scanner currently calls the dashboard on the main server. Now
that the dashboard is on the same Pi, edit `dashboard/pi/.env`:

```ini
BASE_URL=http://localhost:3000
```

Then restart the scanner:

```bash
sudo systemctl restart jondo-face.service
```

## Step 8 — Auto-start the dashboard on boot

Install the included service file:

```bash
cd /home/pi/jondo-time-clock/dashboard/pi
sudo cp jondo-dashboard.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now jondo-dashboard.service
systemctl status jondo-dashboard.service
journalctl -u jondo-dashboard.service -f
```

The dashboard will now start automatically whenever the Pi boots,
and restart itself if it crashes.

## Step 9 — Repoint the kiosk and admins

The web app now lives at the Pi's address. Update:

- The **kiosk screen** browser — set its home page / clock-in URL to
  `http://<pi-ip>:3000`.
- Any **bookmarks** managers use for the dashboard.
- The MySQL **camera URL** setting (Settings page) stays as
  `http://<pi-ip>:8080/stream` — the scanner is still on the Pi, so
  this does not change.

## Step 10 — Decommission the old web app

Once the Pi version is confirmed working, stop the old Node app on
the main server (so two copies aren't both writing to the database).
**Leave MySQL running on the main server** — that's still the
database for everything.

---

## Notes

**Memory.** A Pi 3 has 1GB of RAM. The face scanner is the heavy
user; the Node app is light. The camera schedule helps a lot — while
the camera is "asleep" the scanner pauses recognition, freeing the
CPU for the web app. Check headroom any time with `free -h`.

**The Pi is now a single point of failure** for both clock-in and
face recognition. Keep it on a reliable power supply, and consider an
occasional backup of the `dashboard/.env` and the SD card image.

**Database is remote.** Every page load makes a few queries over the
network to the main server. On a wired LAN this is unnoticeable; if
the Pi is on Wi-Fi, a wired connection will feel snappier and is more
reliable.

**Keep the main server's MySQL reachable.** If the main server is
off, or its firewall/MySQL user blocks the Pi, the dashboard can't
load. The database server needs to stay up.

## Quick reference

```bash
# dashboard
sudo systemctl restart jondo-dashboard.service
journalctl -u jondo-dashboard.service -f

# face scanner
sudo systemctl restart jondo-face.service
journalctl -u jondo-face.service -f
```
