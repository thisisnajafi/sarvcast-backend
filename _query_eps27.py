# -*- coding: utf-8 -*-
import json, urllib.request

env = {}
for line in open(".env", encoding="utf-8"):
    line = line.strip()
    if not line or line.startswith("#") or "=" not in line:
        continue
    k, v = line.split("=", 1)
    env[k.strip()] = v.strip().strip('"').strip("'")

base = env["LOCAL_IMPORT_API_BASE_URL"].rstrip("/")
token = env["LOCAL_IMPORT_API_TOKEN"]
headers = {"Authorization": "Bearer " + token, "Accept": "application/json"}
slug = "27-dastan-hftkhoan-asfndyar"
req = urllib.request.Request(base + f"/story-editor/stories/{slug}/episodes", headers=headers)
eps = json.loads(urllib.request.urlopen(req).read().decode())
data = eps.get("data", eps)
open("_api_episodes27.json", "w", encoding="utf-8").write(json.dumps(data, ensure_ascii=False, indent=2))
