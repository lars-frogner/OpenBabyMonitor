#!/usr/bin/env python3
"""
Generate VAPID key pair for Web Push notifications.
Keys are stored in config/vapid_private.key and config/vapid_public.key.

Run once during setup: python3 generate_vapid_keys.py
Requires: pywebpush (pip install pywebpush)
"""

import os
import pathlib
import sys


def generate():
    try:
        from py_vapid import Vapid
    except ImportError:
        try:
            from pywebpush import Vapid
        except ImportError:
            print('ERROR: pywebpush is not installed. Run: pip3 install pywebpush')
            sys.exit(1)

    bm_dir = os.environ.get('BM_DIR', os.path.dirname(os.path.dirname(__file__)))
    config_dir = pathlib.Path(bm_dir) / 'config'

    private_path = config_dir / 'vapid_private.key'
    public_path  = config_dir / 'vapid_public.key'

    if private_path.exists() and public_path.exists():
        print('VAPID keys already exist. Delete them first to regenerate.')
        print(f'  Public key: {public_path.read_text().strip()}')
        return

    v = Vapid()
    v.generate_keys()

    private_key = v.private_pem().decode() if isinstance(v.private_pem(), bytes) else v.private_pem()
    public_key  = v.public_key.public_bytes(
        encoding=__import__('cryptography').hazmat.primitives.serialization.Encoding.X962,
        format=__import__('cryptography').hazmat.primitives.serialization.PublicFormat.UncompressedPoint
    )
    import base64
    public_key_b64 = base64.urlsafe_b64encode(public_key).rstrip(b'=').decode()

    private_path.write_text(private_key)
    public_path.write_text(public_key_b64)

    # Secure the private key
    os.chmod(private_path, 0o600)

    print('VAPID keys generated successfully.')
    print(f'  Public key (for frontend): {public_key_b64}')
    print(f'  Private key saved to: {private_path}')


if __name__ == '__main__':
    generate()
