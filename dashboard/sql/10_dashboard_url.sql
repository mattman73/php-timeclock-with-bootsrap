-- Public URL of the dashboard, used in outbound notifications.
-- Late-arrival emails, time-off request emails, decision emails,
-- and WhatsApp messages all include a tappable link back to the
-- right page on the dashboard. Set this to the LAN address staff
-- and managers actually reach the dashboard at, e.g.
-- http://192.168.x.x:3000   (no trailing slash).
-- If left blank, the system falls back to http://localhost:PORT,
-- which only works on the dashboard server itself.
ALTER TABLE alert_settings ADD COLUMN dashboard_base_url VARCHAR(255) NOT NULL DEFAULT '';
