#!/usr/bin/env python3
"""
Send Web Push notifications to all subscribed browsers.
Called by listen.py after cry/sound detection.

Usage: send_push.py <notification_type> [confidence]
  notification_type: bad | good | sound | bad_and_good | bad_or_good
"""

import sys
import os
import json
import pathlib

sys.path.append(os.path.join(os.environ.get('BM_DIR', os.path.dirname(__file__)), 'control'))
import database as db_module
import config as config_module

NOTIFICATION_LABELS = {
    'bad':         ('Baby Monitor — Crying alert',    'The baby is crying.'),
    'good':        ('Baby Monitor — Babbling alert',  'The baby is babbling.'),
    'sound':       ('Baby Monitor — Sound alert',     'Significant sound detected.'),
    'bad_and_good':('Baby Monitor — Activity alert',  'The baby is making sounds.'),
    'bad_or_good': ('Baby Monitor — Activity alert',  'Possible baby activity detected.'),
}


def load_vapid_keys():
    """Load VAPID private key from config directory."""
    bm_dir = os.environ.get('BM_DIR', os.path.dirname(os.path.dirname(__file__)))
    private_key_path = pathlib.Path(bm_dir) / 'config' / 'vapid_private.key'
    public_key_path  = pathlib.Path(bm_dir) / 'config' / 'vapid_public.key'
    if not private_key_path.exists() or not public_key_path.exists():
        return None, None
    return private_key_path.read_text().strip(), public_key_path.read_text().strip()


def get_subscriptions(database):
    database.cursor.execute("SELECT endpoint, p256dh, auth FROM `push_subscriptions`")
    return database.cursor.fetchall()


def send_push_notification(subscription, payload, vapid_private, vapid_public, contact_email):
    """Send a single Web Push notification. Requires pywebpush package."""
    try:
        from pywebpush import webpush, WebPushException
        webpush(
            subscription_info={
                'endpoint': subscription['endpoint'],
                'keys': {
                    'p256dh': subscription['p256dh'],
                    'auth':   subscription['auth'],
                }
            },
            data=json.dumps(payload),
            vapid_private_key=vapid_private,
            vapid_claims={
                'sub': 'mailto:' + contact_email,
            }
        )
        return True
    except ImportError:
        return False
    except Exception:
        return False


def main():
    if len(sys.argv) < 2:
        sys.exit(1)

    notification_type = sys.argv[1]
    title, body = NOTIFICATION_LABELS.get(notification_type, ('Baby Monitor', 'Activity detected.'))

    vapid_private, vapid_public = load_vapid_keys()
    if not vapid_private:
        return

    try:
        cfg = config_module.read_config()
    except Exception:
        return

    contact_email = cfg.get('push', {}).get('contact_email', 'admin@babymonitor.local')

    try:
        with db_module.Database.from_config(cfg) as database:
            subscriptions = get_subscriptions(database)
    except Exception:
        return

    stale_endpoints = []
    for sub in subscriptions:
        ok = send_push_notification(
            sub,
            {'title': title, 'body': body, 'type': notification_type},
            vapid_private,
            vapid_public,
            contact_email
        )
        if not ok:
            stale_endpoints.append(sub['endpoint'])

    # Remove stale subscriptions
    if stale_endpoints:
        try:
            with db_module.Database.from_config(cfg) as database:
                for ep in stale_endpoints:
                    database.cursor.execute(
                        "DELETE FROM `push_subscriptions` WHERE endpoint = %s", (ep,)
                    )
                database.connection.commit()
        except Exception:
            pass


if __name__ == '__main__':
    main()
