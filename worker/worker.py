from __future__ import annotations

import configparser
import logging
import time
from pathlib import Path

import requests

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")


def load_config() -> configparser.ConfigParser:
    path = Path(__file__).with_name("config.ini")
    if not path.is_file():
        raise RuntimeError("Create worker/config.ini from config.example.ini")
    config = configparser.ConfigParser()
    config.read(path, encoding="utf-8")
    return config


def main() -> None:
    config = load_config()
    endpoint = config["api"]["base_url"]
    token = config["api"]["worker_token"]
    version = config["worker"].get("version", "unknown")
    session = requests.Session()
    session.headers.update({"Authorization": f"Bearer {token}"})

    while True:
        try:
            response = session.post(endpoint, params={"action": "heartbeat"}, json={"action": "heartbeat", "whatsapp_status": "unknown", "worker_version": version}, timeout=15)
            response.raise_for_status()
            logging.info("Heartbeat accepted: %s", response.json().get("success"))
        except requests.RequestException as exc:
            logging.warning("API unavailable: %s", exc)
        time.sleep(max(1, config["worker"].getint("poll_seconds", 5)))


if __name__ == "__main__":
    main()
