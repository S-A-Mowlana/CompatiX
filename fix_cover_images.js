// fix_cover_images.js
// Run with: node fix_cover_images.js
const fs = require('fs');
const path = require('path');

function updateCache(filePath, imageKey, screenshotKey) {
    const raw = fs.readFileSync(filePath, 'utf-8');
    const data = JSON.parse(raw);
    let updated = 0;

    data.data.forEach(item => {
        const placeholder = /placeholder(-game)?\.png/.test(item[imageKey]);
        const hasScreenshots = Array.isArray(item[screenshotKey]) && item[screenshotKey].length > 0;

        if (placeholder && hasScreenshots) {
            item[imageKey] = item[screenshotKey][0];
            updated++;
        }
    });

    fs.writeFileSync(filePath, JSON.stringify(data, null, 4));
    console.log(`✓ Updated ${updated} entries in ${path.basename(filePath)}`);
}

// Apps: image_url <- screenshots[0]
updateCache(
    path.join(__dirname, 'apps_cache.json'),
    'image_url',
    'screenshots'
);

// Games: image_url <- short_screenshots[0]
updateCache(
    path.join(__dirname, 'games_cache.json'),
    'image_url',
    'short_screenshots'
);
