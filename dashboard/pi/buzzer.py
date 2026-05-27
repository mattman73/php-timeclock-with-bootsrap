"""Optional GPIO passive-buzzer feedback for the Pi scanner.

Drives a passive piezo buzzer on a single GPIO pin so staff get an
audible cue at the kiosk:

  - success(): a short rising two-note chirp when a face is matched
    and the punch is recorded.
  - reject():  a low double-buzz when a face is seen but not
    recognised.

A *passive* buzzer is just a piezo element — the Pi has to drive it
with a square wave at the pitch we want, which is exactly what
gpiozero's PWMOutputDevice does. (An *active* buzzer would ignore
the frequency and play its own fixed tone.)

This module is deliberately fail-soft: if gpiozero isn't installed,
the GPIO pin can't be claimed, or the buzzer is disabled in .env,
every call becomes a silent no-op and the scanner runs exactly as
it did before.

Wiring (passive buzzer or a KY-006 module):
    buzzer signal  -> GPIO 18  (physical pin 12)
    buzzer ground  -> GND      (physical pin 14, right next to it)
On a 3-pin KY-006 module: 'S' = signal, '-' = ground, middle pin
is not connected. Change the pin with BUZZER_PIN in .env.
"""

import logging
import threading
import time

log = logging.getLogger("pi.buzzer")

# Tone patterns: a list of (frequency_hz, duration_sec) steps.
# A frequency of 0 means a silent gap.
_SUCCESS = [(1046, 0.12), (0, 0.04), (1568, 0.18)]   # rising "ding-ding"
_REJECT = [(196, 0.28), (0, 0.10), (196, 0.28)]      # low double-buzz


class Buzzer:
    """Passive piezo buzzer on one GPIO pin. Safe to use even when
    no buzzer is attached — it just stays silent."""

    def __init__(self, enabled=True, pin=18):
        self._dev = None
        self._lock = threading.Lock()

        if not enabled:
            log.info("buzzer disabled (BUZZER_ENABLED=false)")
            return

        try:
            from gpiozero import PWMOutputDevice
            self._dev = PWMOutputDevice(int(pin))
            self._dev.off()
            log.info("buzzer ready on GPIO %d", int(pin))
        except Exception as e:
            self._dev = None
            log.warning("buzzer unavailable (%s: %s) - running silent",
                        type(e).__name__, e)

    def _play(self, pattern):
        dev = self._dev
        if dev is None:
            return
        # One beep at a time — overlapping PWM writes to the same
        # device would garble the tone.
        with self._lock:
            try:
                for freq, dur in pattern:
                    if freq > 0:
                        dev.frequency = freq
                        dev.value = 0.5      # 50% duty cycle = loudest
                    else:
                        dev.value = 0
                    time.sleep(dur)
            except Exception as e:
                log.debug("buzzer play error: %s", e)
            finally:
                try:
                    dev.value = 0
                except Exception:
                    pass

    def _play_async(self, pattern):
        # Beep on a background thread so the detection loop never
        # waits on the speaker.
        if self._dev is None:
            return
        threading.Thread(target=self._play, args=(pattern,),
                          daemon=True).start()

    def success(self):
        """Short rising chirp - face matched, punch recorded."""
        self._play_async(_SUCCESS)

    def reject(self):
        """Low double-buzz - a face was seen but not recognised."""
        self._play_async(_REJECT)

    def close(self):
        dev, self._dev = self._dev, None
        if dev is not None:
            try:
                dev.off()
                dev.close()
            except Exception:
                pass
