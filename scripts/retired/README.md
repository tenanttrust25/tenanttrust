# Retired systemd units

These three timers/services were **dead, not broken in a fixable way**: every unit pointed at
`WorkingDirectory=/root/.openclaw/workspace`, and that directory no longer exists on srv1740221
(the workspace was removed when the box moved off OpenClaw). The Python scripts they ran
(`tenant_alerts.py`, `twilio_sms_forward.py`, `discord_autorole.py`) are gone — no copy on disk,
none in git history, no backup.

They had been failing on every timer tick for weeks, which is why `systemctl --failed` was never
empty and the journal was full of `status=200/CHDIR`. Retired 2026-11-04.

| Unit | Was supposed to do | Still covered by |
|---|---|---|
| `tenant-alerts` (5 min) | N9 + HOS alerts to Telegram | TenantTrust scheduler (`reminders:notify-due`, twice daily, emails admins) for reminders; **N9/HOS Telegram alerting is NOT replaced** |
| `twilio-sms-relay` (10 s) | Poll Twilio for inbound SMS, forward to Steve | In-app Twilio **voice** webhooks only; inbound **SMS** forwarding is NOT replaced |
| `onlydads-autorole` (60 s) | Assign the "Open Door" Discord role to new members | Nothing — Only Dad's Discord role assignment is manual |

## Rebuilding any of these

Each needs its own credentials and a small script; none should be resurrected by pointing at a
new path alone, because the original code is lost.

- **tenant-alerts**: Telegram bot token + chat id; reuse the app's Reminder/N9 tables as the source
  of truth. Prefer implementing it as an artisan command inside TenantTrust so it deploys with the
  app instead of living in `/usr/local/bin` glue.
- **twilio-sms-relay**: Twilio Account SID + auth token + a receiving number, and the number Steve
  forwards to. Twilio's own webhook to a small endpoint is more reliable than polling every 10 s.
- **onlydads-autorole**: Discord bot token with `Manage Roles`, guild id, role id, plus the
  `GUILD_MEMBERS` intent. Discord's `GUILD_MEMBER_ADD` gateway event is better than a 60 s poll.

To reinstate a unit: copy the `.service`/`.timer` pair from this directory to
`/etc/systemd/system/`, fix `WorkingDirectory`/`ExecStart`, then `systemctl daemon-reload &&
systemctl enable --now <name>.timer`.
