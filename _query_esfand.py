# -*- coding: utf-8 -*-
import json, urllib.request, os, re

env = {}
for line in open(".env", encoding="utf-8"):
    line = line.strip()
    if not line or line.startswith("#") or "=" not in line:
        continue
    k, v = line.split("=", 1)
    env[k.strip()] = v.strip().strip('"').strip("'")

base = env["LOCAL_IMPORT_API_BASE_URL"].rstrip("/")
token = env["LOCAL_IMPORT_API_TOKEN"]
req = urllib.request.Request(
    base + "/story-editor/stories",
    headers={"Authorization": "Bearer " + token, "Accept": "application/json"},
)
data = json.loads(urllib.request.urlopen(req).read().decode())
stories = data.get("data", data) if isinstance(data, dict) else data

keywords = ["esfand", "haft", "7esfand", "heft", "اسفند", "هفت", "167"]
hits = []
for s in stories:
    blob = json.dumps(s, ensure_ascii=False).lower()
    if any(k in blob for k in keywords):
        hits.append(s)

# Get episodes for id 167
for s in stories:
    sid = s.get("id") or s.get("story_id")
    if str(sid) == "167":
        slug = s.get("slug") or s.get("id")
        req2 = urllib.request.Request(
            base + f"/story-editor/stories/{slug}/episodes",
            headers={"Authorization": "Bearer " + token, "Accept": "application/json"},
        )
        eps = json.loads(urllib.request.urlopen(req2).read().decode())
        s["_episodes"] = eps.get("data", eps)
        hits.append(s)

out = {"matched": hits, "total_stories": len(stories)}
open("_api_esfand_verify.json", "w", encoding="utf-8").write(
    json.dumps(out, ensure_ascii=False, indent=2)
)
