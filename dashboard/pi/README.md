# Jondo Time Clock — Pi face-recognition scanner

A small Python service that runs on a Raspberry Pi, watches a
camera, and clocks people in / out by face. It calls the dashboard's
`/api/punch` endpoint over HTTP — same effect as a barcode scan.

## What it does and doesn't do

It does:
- Pulls reference photos from the dashboard for every employee
  with `face_opt_in = 1`, and computes 128-d face embeddings
  locally (using dlib via the `face_recognition` library).
- Watches the camera, detects faces, matches against the cache.
- Only acts when the match is confident (low distance) AND the
  same person has been the dominant subject for at least
  `MIN_HOLD_SEC` seconds.
- Won't punch the same person twice within `COOLDOWN_SEC` seconds.
- Reports every match attempt back to the dashboard's audit log
  so admins can debug confidence thresholds.

It doesn't:
- Anti-spoofing. A photo on a phone screen will fool it. If you
  need liveness detection, that's a follow-up project — see
  `Silent-Face-Anti-Spoofing` or use a depth camera (Intel
  RealSense) and add a depth check.
- Train its own model. It uses the dlib pre-trained 128-d face
  recognition model.

## Hardware

- Raspberry Pi 4 (4GB+) or Pi 5. Pi Zero 2 W is too slow.
- A camera. Any of:
  - Pi Camera Module 3 (CSI ribbon) — best image quality.
  - Logitech C920 / similar USB webcam — easiest to set up.
- A small ring-light or under-monitor light if your kiosk spot is
  poorly lit. Lighting is the #1 cause of misidentifications.
- (Optional) A passive piezo buzzer for audible feedback — see
  the "Buzzer" section below.

## One-time install on the Pi

```bash
sudo apt update
sudo apt install -y python3-venv python3-pip python3-dev \
                    cmake build-essential \
                    libopenblas-dev liblapack-dev libjpeg-dev \
                    libv4l-dev v4l-utils \
                    libsm6 libxext6 libxrender1 libgl1 \
                    python3-picamera2 python3-numpy \
                    git
cd ~
git clone <your repo>
cd <project>/dashboard/pi

# IMPORTANT: --system-site-packages so picamera2 (apt-installed)
# is visible inside the venv. Without this, the CSI camera path
# won't work.
python3 -m venv --system-site-packages venv
source venv/bin/activate
pip install --upgrade pip wheel

# Pre-built dlib (no compile). If this fails for your combo, see
# the "dlib install" troubleshooting section below.
pip install dlib-bin

# face_recognition without its own dlib dependency check (dlib-bin
# already provides the dlib module — pip just doesn't know that).
pip install --no-deps face_recognition face_recognition_models
pip install Click Pillow opencv-python-headless requests python-dotenv

cp .env.example .env
# edit .env: set BASE_URL and API_TOKEN
```

To check the camera works (CSI):
```bash
rpicam-hello --timeout 2000   # 2-second preview, needs a screen
rpicam-still -o test.jpg      # capture a still, works headless
```

To check the camera works (USB):
```bash
ls /dev/video*
python -c "import cv2; cap=cv2.VideoCapture(0); ok,f=cap.read(); print('frame', f.shape if ok else 'FAILED'); cap.release()"
```

To verify the scanner's view of the camera (CSI or USB):
```bash
python -c "
from camera import CameraSource
cam = CameraSource()
print('kind:', cam.kind)
frame = cam.read()
print('frame:', frame.shape if frame is not None else 'None')
cam.close()
"
```

## Live camera stream

While `scanner.py` runs it also serves an MJPEG video stream of the
camera, with face-detection boxes drawn on it (red box while
searching, green box + name when matched). This lets the kiosk
screen show staff a live "am I in frame" preview.

- Stream URL:    `http://<pi-ip>:8080/stream`
- Single frame:  `http://<pi-ip>:8080/snapshot`
- Health check:  `http://<pi-ip>:8080/health`

It's **LAN-open** — anyone on the local network can view it, no
authentication. Fine for an internal office network; don't expose
port 8080 to the internet.

To show it on the dashboard kiosk page: in the dashboard's
**Settings**, set "Kiosk camera URL" to `http://<pi-ip>:8080/stream`.

Disable streaming entirely by setting `STREAM_ENABLED=false` in
`.env`, or change the port with `STREAM_PORT`.

## Buzzer (optional)

The scanner can drive a small **passive piezo buzzer** wired to a
GPIO pin, so staff get an audible cue without watching the screen:

- A short rising chirp when a face is matched and the punch is
  recorded.
- A low double-buzz when a face is seen but not recognised
  (throttled so it doesn't buzz on every frame).

Use a *passive* buzzer (the Pi drives the tone) — a bare 2-pin
passive buzzer, or a 3-pin module such as a KY-006. An *active*
buzzer plays its own fixed tone and won't give the two distinct
sounds.

Wiring (default pin):

```
buzzer signal  ->  GPIO 18   (physical pin 12)
buzzer ground  ->  GND       (physical pin 14, next to it)
```

On a 3-pin KY-006 module: `S` = signal, `-` = ground, the middle
pin is left unconnected. A passive buzzer draws only a few mA, so
it can connect straight to the GPIO with no transistor.

The buzzer uses the `gpiozero` library, which ships with Raspberry
Pi OS. If the scanner logs `buzzer unavailable`, install it inside
the venv:

```bash
pip install gpiozero lgpio
```

Settings in `.env`:

- `BUZZER_ENABLED` — `false` to run silently with no buzzer.
- `BUZZER_PIN` — the GPIO (BCM) number the signal wire is on.
- `BUZZER_REJECT_COOLDOWN` — minimum seconds between "not
  recognised" buzzes.

If no buzzer is wired up, leave `BUZZER_ENABLED=true` anyway — the
scanner fails soft and just stays silent.

## Running

```bash
cd /home/pi/<project>/dashboard/pi
source venv/bin/activate
python scanner.py
```

You should see something like:
```
2026-04-29 09:12:01 pi.scanner INFO initial sync from http://… …
2026-04-29 09:12:03 pi.sync   INFO Cache loaded: 6 photos, 6 embeddings, 2 distinct people
2026-04-29 09:12:03 pi.scanner INFO scanner running (threshold=0.50, hold=1.5s, cooldown=60s)
```

Walk in front of the camera. After ~1.5s of holding still you
should see `PUNCH OK: <name> -> in` and the dashboard board will
light up.

## Tuning

Open the dashboard's **Faces → manage** page for an employee — the
"Recent match attempts" section shows you every match the Pi
considered, with the confidence. Use that to dial in:

- **Too many false positives** (people getting punched as the
  wrong person) → lower `MATCH_THRESHOLD` (try 0.45, then 0.40).
- **Too many `rejected: no match within threshold`** (real people
  not getting recognised) → raise `MATCH_THRESHOLD` slightly
  (0.55), capture a couple more reference photos, or improve
  lighting.
- **Same person punching back-and-forth in a single visit** →
  raise `COOLDOWN_SEC`.
- **Slow on Pi 4** → drop `FRAME_SCALE` to 0.4 or 0.33.

## Run as a service (systemd)

```ini
# /etc/systemd/system/jondo-face.service
[Unit]
Description=Jondo Time Clock face scanner
After=network-online.target

[Service]
User=pi
WorkingDirectory=/home/pi/jondo-time-clock/dashboard/pi
ExecStart=/home/pi/jondo-time-clock/dashboard/pi/venv/bin/python scanner.py
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now jondo-face.service
journalctl -u jondo-face.service -f
```

## Compliance notes (UK GDPR)

You **must** have explicit, freely-given, written consent from each
employee whose face is enrolled. The `Faces` page in the dashboard
records the consent timestamp; that's evidence. Make sure:

1. Consent is opt-in — the **Opt in** button is OFF by default.
2. There's a non-biometric alternative (badge / password) and
   it's actually offered, not buried.
3. You've done a Data Protection Impact Assessment for it.
4. The "Erase all face data" button on each employee's Faces page
   actually works (it does — it soft-deletes photos and removes
   audit history). Test it before promising it to a real person.

If you're not ready to tick those boxes yet, leave `face_opt_in` =
0 for everyone. The Pi will refuse to match against anyone who
isn't opted in, so no biometric data is processed.
