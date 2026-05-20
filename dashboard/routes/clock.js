// Public kiosk routes. No login needed.
//   GET  /            kiosk page (badge scan)
//   POST /            handle a barcode submit
//   GET  /password    password-fallback form
//   POST /password    handle a password submit

const express = require('express');
const { punchByBadge, punchByPassword } = require('../services/clockin');
const { query } = require('../db');

const router = express.Router();

function clientIp(req) {
    return (req.headers['x-forwarded-for'] || '').split(',')[0].trim()
        || req.socket.remoteAddress || '';
}

// Kiosk settings (result-screen duration + camera stream URL),
// cached 30s so a busy kiosk isn't hitting the DB every request.
let cachedKiosk = { resultSecs: 5, cameraUrl: '', fetchedAt: 0 };
async function getKioskSettings() {
    const now = Date.now();
    if (now - cachedKiosk.fetchedAt < 30000) return cachedKiosk;
    try {
        const rows = await query(
            `SELECT kiosk_result_seconds, kiosk_camera_url
             FROM alert_settings WHERE id = 1`
        );
        if (rows[0]) {
            const v = Number(rows[0].kiosk_result_seconds);
            cachedKiosk = {
                resultSecs: (Number.isFinite(v) && v >= 1 && v <= 60) ? v : 5,
                cameraUrl: rows[0].kiosk_camera_url || '',
                fetchedAt: now,
            };
        }
    } catch (e) {
        // DB unreachable or columns not migrated yet — keep cached defaults.
    }
    return cachedKiosk;
}

router.get('/', async (req, res, next) => {
    try {
        const k = await getKioskSettings();
        res.render('clock', {
            title: 'Clock In / Out',
            flash: null,
            isError: false,
            cameraUrl: k.cameraUrl,
        });
    } catch (err) { next(err); }
});

router.post('/', async (req, res, next) => {
    try {
        const k = await getKioskSettings();
        const badge = (req.body.BadgeID || '').trim();
        const notes = (req.body.notes || '').trim().slice(0, 240);
        if (!badge) {
            return res.render('clock', {
                title: 'Clock In / Out',
                flash: 'Please scan a badge.',
                isError: true,
                cameraUrl: k.cameraUrl,
            });
        }
        const result = await punchByBadge(badge, { notes, ip: clientIp(req) });
        if (!result.ok) {
            return res.render('clock', {
                title: 'Clock In / Out',
                flash: result.reason === 'unknown badge'
                    ? 'Badge not recognised. Try again or use password sign-in.'
                    : 'This account is disabled. See your manager.',
                isError: true,
                cameraUrl: k.cameraUrl,
            });
        }
        return res.render('clock-result', {
            title: result.action === 'in' ? 'Clocked in' : 'Clocked out',
            displayname: result.displayname,
            action: result.action,
            redirectSecs: k.resultSecs,
        });
    } catch (err) { next(err); }
});

router.get('/password', (req, res) => {
    res.render('clock-password', { title: 'Sign in', flash: null });
});

router.post('/password', async (req, res, next) => {
    try {
        const k = await getKioskSettings();
        const username = (req.body.username || '').trim();
        const password = req.body.password || '';
        const notes = (req.body.notes || '').trim().slice(0, 240);
        if (!username || !password) {
            return res.render('clock-password', {
                title: 'Sign in',
                flash: 'Username and password are both required.',
            });
        }
        const result = await punchByPassword(username, password, { notes, ip: clientIp(req) });
        if (!result.ok) {
            return res.render('clock-password', {
                title: 'Sign in',
                flash: result.reason === 'employee disabled'
                    ? 'This account is disabled. See your manager.'
                    : 'Username or password not recognised.',
            });
        }
        return res.render('clock-result', {
            title: result.action === 'in' ? 'Clocked in' : 'Clocked out',
            displayname: result.displayname,
            action: result.action,
            redirectSecs: k.resultSecs,
        });
    } catch (err) { next(err); }
});

module.exports = router;
