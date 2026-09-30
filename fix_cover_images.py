# fix_cover_images.py
import json, os

def update_cache(file_path, image_key, screenshot_key):
    with open(file_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    updated = 0
    for item in data.get('data', []):
        img = item.get(image_key, '')
        if 'placeholder' in img and item.get(screenshot_key):
            screenshots = item[screenshot_key]
            if isinstance(screenshots, list) and screenshots:
                item[image_key] = screenshots[0]
                updated += 1
    with open(file_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)
    print(f"✓ Updated {updated} entries in {os.path.basename(file_path)}")

# Paths
base = os.path.dirname(__file__)
apps = os.path.join(base, 'apps_cache.json')
games = os.path.join(base, 'games_cache.json')

update_cache(apps, 'image_url', 'screenshots')
update_cache(games, 'image_url', 'short_screenshots')
