// Alert + SMTP + time-off + kiosk settings.

const express = require('express');
const { query } = require('../db');
const { requireAdmin } = require('../services/auth');
const { sendMail, loadSettings } = require('../services/mailer');
const { runOnce } = require('../services/alerts');

const router = express.Router();
router.use(requireAdmin);

router.get('/', async (req, res, next) => {
    try {
        const settings = await loadSettings();
        res.render('settings', { title: 'Settings', settings });
    } catch (err) { next(err); }
});

router.post('/', async (req, res, next) => {
    const b = req.body;
    try {
        const setPass = b.smtp_pass && b.smtp_pass !== '__keep__';
        const sql =
            `UPDATE alert_settings SET
                manager_email = ?,
                notify_manager = ?,
                notify_employee = ?,
                smtp_host = ?,
                smtp_port = ?,
                smtp_user = ?,
                ${setPass ? 'smtp_pass = ?,' : ''}
                smtp_from = ?,
                smtp_secure = ?,
                company_name = ?,
                timeoff_notify_email = ?,
                min_holiday_notice_days = ?,
                standard_workday_hours = ?,
                kiosk_result_seconds = ?,
                kiosk_camera_url = ?
             WHERE id = 1`;
        const params = [
            (b.manager_email || '').trim(),
            b.notify_manager ? 1 : 0,
            b.notify_employee ? 1 : 0,
            (b.smtp_host || '').trim(),
            Number(b.smtp_port) || 587,
            (b.smtp_user || '').trim(),
        ];
        if (setPass) params.push(b.smtp_pass);
        params.push((b.smtp_from || '').trim());
        params.push(b.smtp_secure ? 1 : 0);
        params.push((b.company_name || 'Jondo').trim());
        params.push((b.timeoff_notify_email || '').trim());

        let notice = parseInt(b.min_holiday_notice_days, 10);
        if (!Number.isFinite(notice) || notice < 0) notice = 14;
        params.push(notice);

        let wdHours = parseFloat(b.standard_workday_hours);
        if (!Number.isFinite(wdHours) || wdHours <= 0 || wdHours > 24) wdHours = 8;
        params.push(wdHours);

        let kioskSecs = parseInt(b.kiosk_result_seconds, 10);
        if (!Number.isFinite(kioskSecs) || kioskSecs < 1 || kioskSecs > 60) kioskSecs = 5;
        params.push(kioskSecs);

        params.push((b.kiosk_camera_url || '').trim());

        await query(sql, params);
        req.flash('success', 'Settings saved.');
        res.redirect('/settings');
    } catch (err) { next(err); }
});

// Send a test email to the manager address using current settings.
router.post('/test-email', async (req, res, next) => {
    try {
        const settings = await loadSettings();
        if (!settings || !settings.manager_email) {
            req.flash('error', 'Please save a manager email address first.');
            return res.redirect('/settings');
        }
        const r = await sendMail({
            to: settings.manager_email,
            subject: '[Jondo] Test email from time clock dashboard',
            text: 'This is a test email confirming SMTP is configured correctly.',
        });
        if (r.sent) req.flash('success', `Test email sent to ${settings.manager_email}.`);
        else req.flash('error', `Test failed: ${r.reason}`);
        res.redirect('/settings');
    } catch (err) { next(err); }
});

// Manually trigger the alert checker (useful while testing).
router.post('/run-alerts', async (req, res, next) => {
    try {
        const r = await runOnce();
        req.flash('success', `Alert run complete: checked ${r.checked} scheduled employees, ${r.alerted} new alerts.`);
        res.redirect('/dashboard');
    } catch (err) { next(err); }
});

module.exports = router;
