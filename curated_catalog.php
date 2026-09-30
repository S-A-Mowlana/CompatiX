<?php
/**
 * Canonical requirements for the titles that have a verified local profile.
 * Keep this shared by the libraries and the compatibility APIs so cached
 * metadata cannot silently replace the values shown in the checker.
 */
function compatix_canonical_requirements(string $title): ?array {
    $aliases = [
        'cyberpunk' => 'Cyberpunk 2077',
        'gta' => 'Grand Theft Auto V',
        'gta 5' => 'Grand Theft Auto V',
        'gta v' => 'Grand Theft Auto V',
        'elden ring' => 'Elden Ring',
        'baldurs gate 3' => "Baldur's Gate 3",
        'photoshop' => 'Adobe Photoshop 2024',
        'vscode' => 'Visual Studio Code',
        'vs code' => 'Visual Studio Code',
    ];
    $normalized = compatix_curated_normalize($title);
    $normalized = compatix_curated_normalize($aliases[$normalized] ?? $normalized);
    $requirements = [
        'Cyberpunk 2077' => ['min_cpu' => 'Intel Core i7-9700 / AMD Ryzen 5 3600', 'min_gpu' => 'NVIDIA GeForce RTX 2060 / AMD Radeon RX 5700', 'min_ram' => 8, 'min_storage' => 160, 'min_os' => 'Windows 10/11 64-bit', 'rec_cpu' => 'Intel Core i7-12700 / AMD Ryzen 7 5800X', 'rec_gpu' => 'NVIDIA GeForce RTX 3070 / AMD Radeon RX 6800 XT', 'rec_ram' => 16, 'rec_storage' => 160, 'rec_os' => 'Windows 10/11 64-bit', 'source' => 'CompatiX verified requirements'],
        'Grand Theft Auto V' => ['min_cpu' => 'Intel Core i5-3470 / AMD FX-8350', 'min_gpu' => 'NVIDIA GTX 660 / AMD HD 7870', 'min_ram' => 8, 'min_storage' => 110, 'min_os' => 'Windows 10/11 64-bit', 'rec_cpu' => 'Intel Core i7-4770 / AMD Ryzen 5 2600', 'rec_gpu' => 'NVIDIA GTX 1060 / AMD RX 580', 'rec_ram' => 16, 'rec_storage' => 110, 'rec_os' => 'Windows 10/11 64-bit', 'source' => 'CompatiX verified requirements'],
        'Elden Ring' => ['min_cpu' => 'Intel Core i7-10700 / AMD Ryzen 5 3600', 'min_gpu' => 'NVIDIA GeForce RTX 2080 Ti / AMD Radeon RX 5700', 'min_ram' => 12, 'min_storage' => 60, 'min_os' => 'Windows 10/11 64-bit', 'rec_cpu' => 'Intel Core i7-11700K / AMD Ryzen 7 5800X', 'rec_gpu' => 'NVIDIA RTX 3070 / AMD RX 6800', 'rec_ram' => 16, 'rec_storage' => 60, 'rec_os' => 'Windows 10/11 64-bit', 'source' => 'CompatiX verified requirements'],
        "Baldur's Gate 3" => ['min_cpu' => 'Intel Core i7-9700 / AMD Ryzen 5 3600', 'min_gpu' => 'NVIDIA GeForce RTX 2070 / AMD Radeon RX 5600 XT', 'min_ram' => 8, 'min_storage' => 150, 'min_os' => 'Windows 10/11 64-bit', 'rec_cpu' => 'Intel Core i7-10700K / AMD Ryzen 7 5800X', 'rec_gpu' => 'NVIDIA RTX 3060 Ti / AMD RX 6700 XT', 'rec_ram' => 16, 'rec_storage' => 150, 'rec_os' => 'Windows 10/11 64-bit', 'source' => 'CompatiX verified requirements'],
    ];
    foreach ($requirements as $name => $profile) {
        if ($normalized === compatix_curated_normalize($name) || str_contains($normalized, compatix_curated_normalize($name)) || str_contains(compatix_curated_normalize($name), $normalized)) return $profile + ['name' => $name];
    }
    return null;
}
/**
 * The CompatiX editorial manifest.  Order is intentional: it is the user's
 * supplied order with duplicates removed, capped at 250 entries per library.
 * Nothing outside these lists may be exposed by a library endpoint.
 */
function compatix_curated_normalize(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim((string)preg_replace('/\s+/', ' ', $value));
}

function compatix_curated_game_genres(string $genre, array $genres = []): array {
    $parts = [];
    foreach (array_merge($genres, [$genre]) as $value) {
        foreach (preg_split('/[,\/|]+/', (string)$value) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $key = compatix_curated_normalize($part);
            if ($key === 'action adventure') {
                $parts[] = 'Action';
                $parts[] = 'Adventure';
            } elseif ($key === 'action rpg' || $key === 'rpg action') {
                $parts[] = 'Action';
                $parts[] = 'RPG';
            } elseif ($key === 'adventure rpg' || $key === 'rpg adventure') {
                $parts[] = 'Adventure';
                $parts[] = 'RPG';
            } else {
                $parts[] = $part;
            }
        }
    }
    $seen = [];
    $result = [];
    foreach ($parts as $part) {
        $key = compatix_curated_normalize($part);
        if ($key !== '' && !isset($seen[$key])) {
            $seen[$key] = true;
            $result[] = $part;
        }
    }
    return $result ?: ['Game'];
}

function compatix_curated_game_year(string $name, ?string $released = null): string {
    if ($released !== null && preg_match('/^(\d{4})/', trim($released), $matches)) return $matches[1];
    $years = [
        'Elden Ring'=>'2022','The Witcher 3: Wild Hunt'=>'2015','The Witcher 3'=>'2015','Counter-Strike 2'=>'2023','Cyberpunk 2077'=>'2020','Grand Theft Auto V'=>'2013','Grand Theft Auto IV'=>'2008','Grand Theft Auto: San Andreas'=>'2004','Grand Theft Auto: Vice City'=>'2002','Grand Theft Auto III'=>'2001','Red Dead Redemption 2'=>'2018','Minecraft'=>'2011','Fortnite'=>'2017','Valorant'=>'2020','League of Legends'=>'2009','Apex Legends'=>'2019','Call of Duty: Modern Warfare III'=>'2023','EA Sports FC 25'=>'2024','EA Sports FC 26'=>'2025','Civilization VI'=>'2016','Civilization VII'=>'2025','Baldur\'s Gate 3'=>'2023','Hogwarts Legacy'=>'2023','Starfield'=>'2023','Diablo IV'=>'2023','God of War'=>'2018','Doom Eternal'=>'2020','Marvel\'s Spider-Man Remastered'=>'2018','Forza Horizon 5'=>'2021','Overwatch 2'=>'2022','Rocket League'=>'2015','Stardew Valley'=>'2016','Hades'=>'2020','Terraria'=>'2011','Among Us'=>'2018','Dota 2'=>'2013','PUBG: Battlegrounds'=>'2017','Tom Clancy\'s Rainbow Six Siege'=>'2015','Destiny 2'=>'2017','Sekiro: Shadows Die Twice'=>'2019','Dark Souls III'=>'2016','Resident Evil 4'=>'2023','Sea of Thieves'=>'2018','No Man\'s Sky'=>'2016','Fall Guys'=>'2020','It Takes Two'=>'2021','A Way Out'=>'2018','Subnautica'=>'2018','F1 24'=>'2024','F1 25'=>'2025','Factorio'=>'2020','Hollow Knight'=>'2017','Microsoft Flight Simulator'=>'2020','Microsoft Flight Simulator 2024'=>'2024','The Sims 4'=>'2014','Assassin\'s Creed Valhalla'=>'2020','Tomb Raider'=>'2013','Titanfall 2'=>'2016','Warframe'=>'2013','Helldivers 2'=>'2024','Palworld'=>'2024','Black Myth: Wukong'=>'2024','The Last of Us Part I'=>'2022','Fallout 4'=>'2015','Left 4 Dead 2'=>'2009','Counter-Strike'=>'2000','Doom'=>'1993','StarCraft II'=>'2010','Warcraft III: Reforged'=>'2020','Celeste'=>'2018','Undertale'=>'2015','Cuphead'=>'2017','Ori and the Blind Forest'=>'2015','Ori and the Will of the Wisps'=>'2020','Divinity: Original Sin 2'=>'2017','Mass Effect Legendary Edition'=>'2021','Far Cry 3'=>'2012','Assassin\'s Creed IV: Black Flag'=>'2013','The Elder Scrolls Online'=>'2014','Street Fighter 6'=>'2023','Tekken 8'=>'2024','Mortal Kombat 11'=>'2019','Need for Speed Heat'=>'2019','Euro Truck Simulator 2'=>'2012','Cities: Skylines'=>'2015','Age of Empires II: Definitive Edition'=>'2019','Age of Empires IV'=>'2021','Total War: Warhammer III'=>'2022','Crusader Kings III'=>'2020','Path of Exile 2'=>'2024','Hades II'=>'2024','Hollow Knight: Silksong'=>'2025','The Forest'=>'2018','Sons of the Forest'=>'2023','Valheim'=>'2021','Rust'=>'2018','DayZ'=>'2013','7 Days to Die'=>'2013','Project Zomboid'=>'2013','RimWorld'=>'2018','Satisfactory'=>'2020','Roblox'=>'2006','Phasmophobia'=>'2020','Dead by Daylight'=>'2016','The Sims 3'=>'2009','Planet Zoo'=>'2019','Jurassic World Evolution 2'=>'2021','LEGO Star Wars: The Skywalker Saga'=>'2022','Marvel\'s Spider-Man: Miles Morales'=>'2022','Guardians of the Galaxy'=>'2021','Black Myth: Wukong'=>'2024'
    ];
    if (isset($years[$name])) return $years[$name];
    if (preg_match('/\b(20\d{2}|19\d{2})\b/', $name, $matches)) return $matches[1];
    return '2020';
}

function compatix_curated_game_titles(): array {
    static $titles = null;
    if ($titles !== null) return $titles;
    $raw = <<<'LIST'
Minecraft|Grand Theft Auto V|Grand Theft Auto IV|Grand Theft Auto: San Andreas|Grand Theft Auto: Vice City|Grand Theft Auto III|Grand Theft Auto: Episodes from Liberty City|Grand Theft Auto: The Trilogy â€“ The Definitive Edition|Red Dead Redemption 2|Red Dead Redemption|Cyberpunk 2077|The Witcher 3: Wild Hunt|The Witcher 2: Assassins of Kings|The Witcher: Enhanced Edition|Elden Ring|Dark Souls Remastered|Dark Souls II|Dark Souls III|Sekiro: Shadows Die Twice|Bloodborne|Lies of P|Black Myth: Wukong|Hogwarts Legacy|Starfield|Skyrim Special Edition|Fallout 4|Fallout: New Vegas|Fallout 3|Fallout 76|The Elder Scrolls Online|Oblivion Remastered|Baldur's Gate 3|Divinity: Original Sin 2|Dragon Age: Inquisition|Dragon Age: The Veilguard|Mass Effect Legendary Edition|Mass Effect Andromeda|Kingdom Come: Deliverance|Kingdom Come: Deliverance II|Assassin's Creed|Assassin's Creed II|Assassin's Creed Brotherhood|Assassin's Creed Revelations|Assassin's Creed III|Assassin's Creed IV: Black Flag|Assassin's Creed Rogue|Assassin's Creed Unity|Assassin's Creed Syndicate|Assassin's Creed Origins|Assassin's Creed Odyssey|Assassin's Creed Valhalla|Assassin's Creed Mirage|Far Cry 3|Far Cry 4|Far Cry 5|Far Cry 6|Far Cry New Dawn|Watch Dogs|Watch Dogs 2|Watch Dogs: Legion|Tom Clancy's Rainbow Six Siege|Tom Clancy's Ghost Recon Wildlands|Tom Clancy's Ghost Recon Breakpoint|Tom Clancy's The Division|Tom Clancy's The Division 2|Splinter Cell Blacklist|Hitman|Hitman 2|Hitman 3|Hitman: World of Assassination|Resident Evil 2|Resident Evil 3|Resident Evil 4|Resident Evil 5|Resident Evil 6|Resident Evil 7|Resident Evil Village|Resident Evil 0|Resident Evil Revelations|Resident Evil Revelations 2|Devil May Cry 5|Devil May Cry 4|Street Fighter 6|Street Fighter V|Tekken 8|Tekken 7|Mortal Kombat 1|Mortal Kombat 11|Mortal Kombat X|Injustice 2|Dragon Ball FighterZ|Dragon Ball: Sparking! ZERO|EA Sports FC 26|EA Sports FC 25|FIFA 23|FIFA 22|FIFA 21|eFootball|Football Manager 2026|Football Manager 2025|NBA 2K26|NBA 2K25|WWE 2K26|WWE 2K25|F1 25|F1 24|F1 23|Forza Horizon 6|Forza Horizon 5|Forza Horizon 4|Forza Motorsport|Need for Speed Unbound|Need for Speed Heat|Need for Speed Payback|Need for Speed Rivals|Need for Speed Most Wanted|Need for Speed Hot Pursuit Remastered|The Crew Motorfest|The Crew 2|Dirt 5|Dirt Rally 2.0|Assetto Corsa|Assetto Corsa Competizione|Euro Truck Simulator 2|American Truck Simulator|Farming Simulator 25|Microsoft Flight Simulator 2024|Microsoft Flight Simulator|BeamNG.drive|CarX Drift Racing Online|Rocket League|Fortnite|Counter-Strike 2|Counter-Strike|Dota 2|League of Legends|Valorant|Overwatch 2|Apex Legends|PUBG: Battlegrounds|Call of Duty: Warzone|Call of Duty: Black Ops 6|Call of Duty: Black Ops 7|Call of Duty: Modern Warfare III|Call of Duty: Modern Warfare II|Call of Duty: Vanguard|Call of Duty: Black Ops Cold War|Call of Duty: Modern Warfare|Call of Duty: Black Ops III|Call of Duty: WWII|Call of Duty: Advanced Warfare|Call of Duty: Infinite Warfare|Battlefield 2042|Battlefield V|Battlefield 1|Battlefield 4|Battlefield 3|Halo Infinite|Halo: The Master Chief Collection|Halo 5: Forge|Gears 5|Gears of War: Reloaded|Doom Eternal|Doom|Doom 3|Wolfenstein: The New Order|Wolfenstein II: The New Colossus|Quake|Titanfall 2|Destiny 2|Warframe|War Thunder|World of Tanks|World of Warships|Helldivers 2|Deep Rock Galactic|Sea of Thieves|No Man's Sky|Palworld|ARK: Survival Evolved|ARK: Survival Ascended|Rust|DayZ|7 Days to Die|Valheim|Sons of the Forest|The Forest|Subnautica|Subnautica: Below Zero|Grounded|Raft|Terraria|Stardew Valley|Don't Starve Together|Project Zomboid|RimWorld|Factorio|Satisfactory|Cities: Skylines|Cities: Skylines II|Civilization VI|Civilization VII|Age of Empires II: Definitive Edition|Age of Empires IV|Total War: Warhammer III|Total War: Three Kingdoms|Crusader Kings III|Europa Universalis IV|StarCraft II|Warcraft III: Reforged|Diablo IV|Diablo III|Path of Exile|Path of Exile 2|Hades|Hades II|Hollow Knight|Hollow Knight: Silksong|Celeste|Dead Cells|Cuphead|Ori and the Blind Forest|Ori and the Will of the Wisps|Undertale|Deltarune|Among Us|Fall Guys|Roblox|Human: Fall Flat|Gang Beasts|It Takes Two|Split Fiction|A Way Out|Phasmophobia|Dead by Daylight|Left 4 Dead 2|Lethal Company|Content Warning|Escape the Backrooms|The Sims 4|The Sims 3|Planet Zoo|Planet Coaster 2|Jurassic World Evolution 2|LEGO Star Wars: The Skywalker Saga|Marvel's Spider-Man Remastered|Marvel's Spider-Man: Miles Morales|Marvel's Guardians of the Galaxy|God of War|God of War RagnarÃ¶k
LIST;
    $seen = [];
    foreach (explode('|', $raw) as $title) { $key = compatix_curated_normalize($title); if ($key !== '' && !isset($seen[$key])) $seen[$key] = trim($title); }
    return $titles = array_slice(array_values($seen), 0, 250);
}

function compatix_curated_app_titles(): array {
    static $titles = null;
    if ($titles !== null) return $titles;
    // Keep the public app library focused on software ordinary PC users recognize.
    $raw = <<<'LIST'
Google Chrome|Microsoft Edge|Mozilla Firefox|Brave|Opera|WhatsApp|Discord|Telegram Desktop|Spotify|Netflix|YouTube|Zoom|Microsoft Teams|Skype|Slack|Microsoft Word|Microsoft Excel|Microsoft PowerPoint|Microsoft OneNote|Microsoft Outlook|Microsoft 365|Google Drive|Google Docs|Google Sheets|Dropbox|OneDrive|Adobe Photoshop|Adobe Illustrator|Adobe Premiere Pro|Adobe Acrobat Reader|Adobe Lightroom|Canva|DaVinci Resolve|CapCut|OBS Studio|VLC Media Player|Audacity|Steam|Epic Games Launcher|Xbox App|EA App|ChatGPT|Google Gemini|Microsoft Copilot|7-Zip|WinRAR|CCleaner|Notion|Trello|Figma
LIST;
    $seen = [];
    foreach (explode('|', $raw) as $title) { $key = compatix_curated_normalize($title); if ($key !== '' && !isset($seen[$key])) $seen[$key] = trim($title); }
    return $titles = array_values($seen);
    $raw = <<<'LIST'
Google Chrome|Mozilla Firefox|Microsoft Edge|Opera|Opera GX|Brave|Vivaldi|Safari|Arc Browser|Tor Browser|Microsoft Word|Microsoft Excel|Microsoft PowerPoint|Microsoft OneNote|Microsoft Outlook|Microsoft Access|Microsoft Publisher|Microsoft Teams|Microsoft 365|LibreOffice Writer|LibreOffice Calc|LibreOffice Impress|LibreOffice|WPS Office|Notion|Evernote|Obsidian|Google Drive|Google Docs|Google Sheets|Google Slides|Dropbox|OneDrive|iCloud|Adobe Acrobat Reader|Adobe Acrobat Pro|Adobe Photoshop|Adobe Illustrator|Adobe Premiere Pro|Adobe After Effects|Adobe Lightroom|Adobe InDesign|Adobe Audition|Adobe Animate|Adobe XD|Adobe Creative Cloud|DaVinci Resolve|DaVinci Resolve Studio|CapCut|Filmora|VEGAS Pro|HandBrake|OBS Studio|Streamlabs Desktop|Audacity|FL Studio|Ableton Live|Ableton Live Lite|Pro Tools|Reaper|LMMS|Blender|Autodesk Maya|Autodesk 3ds Max|Autodesk AutoCAD|Autodesk Fusion|SketchUp|Unity Hub|Unity Editor|Unreal Engine|Godot Engine|Visual Studio Code|Visual Studio|JetBrains IntelliJ IDEA|JetBrains PyCharm|JetBrains WebStorm|JetBrains PhpStorm|JetBrains CLion|JetBrains Rider|Android Studio|Eclipse|NetBeans|Sublime Text|Notepad++|Cursor|Git|GitHub Desktop|GitKraken|Docker Desktop|Postman|Insomnia|XAMPP|WampServer|Laragon|MySQL Workbench|phpMyAdmin|FileZilla|WinSCP|PuTTY|Termius|Windows Terminal|PowerShell|Command Prompt|Ubuntu|VMware Workstation|VirtualBox|Parallels Desktop|Microsoft SQL Server Management Studio|MongoDB Compass|DBeaver|SQLiteStudio|Python|Anaconda|Node.js|npm|Yarn|Java|OpenJDK|.NET SDK|CMake|Maven|Gradle|Microsoft Visual C++|WinRAR|7-Zip|WinZip|PeaZip|Bandizip|VLC Media Player|Windows Media Player|MPC-HC|MPC-BE|PotPlayer|KMPlayer|Kodi|Plex|Spotify|iTunes|Apple Music|Tidal|Discord|Telegram Desktop|WhatsApp Desktop|Signal Desktop|Zoom|Skype|Slack|Google Meet|Microsoft Remote Desktop|TeamViewer|AnyDesk|RustDesk|Steam|Epic Games Launcher|GOG Galaxy|Ubisoft Connect|EA App|Rockstar Games Launcher|Riot Client|Xbox App|PlayStation Plus PC App|NVIDIA App|AMD Software: Adrenalin Edition|Intel Graphics Command Center|MSI Afterburner|Razer Synapse|Logitech G Hub|Corsair iCUE|ASUS Armoury Crate|MSI Center|Alienware Command Center|HWMonitor|HWiNFO|CPU-Z|GPU-Z|CrystalDiskInfo|CrystalDiskMark|Speccy|Geekbench|3DMark|FurMark|PassMark PerformanceTest|AIDA64|Malwarebytes|Bitdefender|Avast Free Antivirus|AVG Antivirus|ESET Internet Security|Kaspersky|Norton 360|McAfee|Windows Security|Microsoft Defender|AdwCleaner|CCleaner|BleachBit|Revo Uninstaller|Everything|PowerToys|ShareX|Greenshot|Snipping Tool|Lightshot|Paint.NET|GIMP|Krita|Inkscape|Affinity Photo|Affinity Designer|Affinity Publisher|Canva|Figma|CorelDRAW|Corel PaintShop Pro|Clip Studio Paint|ShareFactory|Foxit PDF Reader|SumatraPDF|PDF-XChange Editor|Calibre|Kindle|Zotero|Mendeley|qBittorrent|Transmission|ÂµTorrent|BitTorrent|NordVPN|ExpressVPN|Proton VPN|Surfshark|Mullvad VPN|OpenVPN|WireGuard|Proton Mail|Thunderbird|Bitwarden|1Password|KeePassXC|Google Earth Pro|Google Earth|Steam Link|Moonlight|Sunshine|Parsec|GeForce NOW|Xbox Cloud Gaming|RStudio|MATLAB
LIST;
    $seen = [];
    foreach (explode('|', $raw) as $title) { $key = compatix_curated_normalize($title); if ($key !== '' && !isset($seen[$key])) $seen[$key] = trim($title); }
    return $titles = array_slice(array_values($seen), 0, 250);
}

function compatix_curated_game_genre(string $title): string {
    $t = strtolower($title);
    if (preg_match('/fifa|sports fc|football manager|nba 2k|wwe 2k|f1 |rocket league/', $t)) return 'Sports';
    if (preg_match('/forza|need for speed|crew|dirt|assetto|truck|beamng|carx/', $t)) return 'Racing';
    if (preg_match('/civilization|age of empires|total war|crusader|europa|starcraft|warcraft|rimworld|factorio|satisfactory|cities/', $t)) return 'Strategy';
    if (preg_match('/resident evil|dead by daylight|phasmophobia|lethal company|sons of the forest|the forest/', $t)) return 'Horror';
    if (preg_match('/counter|valorant|overwatch|apex|pubg|call of duty|battlefield|halo|doom|wolfenstein|quake|titanfall|warframe|war thunder|world of tanks|helldivers|deep rock|left 4 dead/', $t)) return 'Shooter';
    if (preg_match('/souls|elden|sekiro|witcher|cyberpunk|fallout|skyrim|oblivion|baldur|divinity|dragon age|mass effect|kingdom come|diablo|path of exile|hades/', $t)) return 'RPG';
    if (preg_match('/sims|planet|stardew|farming|flight simulator/', $t)) return 'Simulation';
    return 'Action Adventure';
}

function compatix_curated_app_category(string $title): string {
    $t = strtolower($title);
    if (in_array($title, ['Google Chrome', 'Mozilla Firefox', 'Microsoft Edge', 'Opera', 'Opera GX', 'Brave', 'Vivaldi', 'Safari', 'Arc Browser', 'Tor Browser'], true)) return 'Browser';
    if (preg_match('/word|excel|powerpoint|onenote|outlook|access|publisher|office|notion|evernote|obsidian|drive|docs|sheets|slides|dropbox|onedrive|icloud/', $t)) return 'Productivity';
    if (preg_match('/adobe|davinci|capcut|filmora|vegas|handbrake|obs studio|streamlabs|audacity|fl studio|ableton|pro tools|reaper|lmms/', $t)) return 'Creative';
    if (preg_match('/blender|maya|3ds|max|autocad|fusion|sketchup|unity|unreal|godot/', $t)) return '3D & Game Development';
    if (preg_match('/visual studio|jetbrains|android studio|eclipse|netbeans|sublime|notepad|cursor|git|docker|postman|insomnia|xampp|wamp|laragon|mysql|phpmyadmin|filezilla|winscp|putty|termius|terminal|powershell|command prompt|ubuntu|vmware|virtualbox|parallels|mongodb|dbeaver|sqlite|python|anaconda|node|npm|yarn|java|openjdk|\.net|cmake|maven|gradle/', $t)) return 'Development';
    if (preg_match('/vlc|media player|mpc|potplayer|kmplayer|kodi|plex|spotify|itunes|apple music|tidal/', $t)) return 'Media';
    if (preg_match('/discord|telegram|whatsapp|signal|zoom|skype|slack|meet|remote desktop|teamviewer|anydesk|rustdesk/', $t)) return 'Communication';
    if (preg_match('/steam|epic|gog|ubisoft|ea app|rockstar|riot|xbox|playstation|nvidia|amd|intel graphics|msi|razer|logitech|corsair|armoury|alienware/', $t)) return 'Gaming & Hardware';
    if (preg_match('/malware|bitdefender|avast|avg|eset|kaspersky|norton|mcafee|security|defender|adwcleaner|ccleaner|bleachbit|revo|everything|powertoys|sharex|greenshot|snipping|lightshot/', $t)) return 'Utilities & Security';
    return 'Utilities';
}

function compatix_curated_app_profile(string $title): array {
    $profiles = [
        'Google Chrome' => ['category' => 'Browser', 'os' => 'Windows, macOS, Linux', 'slug' => 'googlechrome', 'description' => 'Google Chrome is a fast, widely used web browser with strong support for modern websites, extensions and Google services.'],
        'Microsoft Edge' => ['category' => 'Browser', 'os' => 'Windows, macOS, Linux', 'slug' => 'microsoftedge', 'description' => 'Microsoft Edge is a Chromium-based browser with Microsoft account sync, built-in security and productivity features.'],
        'Mozilla Firefox' => ['category' => 'Browser', 'os' => 'Windows, macOS, Linux', 'slug' => 'firefox', 'description' => 'Mozilla Firefox is an independent web browser focused on privacy, customization and standards support.'],
        'Brave' => ['category' => 'Browser', 'os' => 'Windows, macOS, Linux', 'slug' => 'brave', 'description' => 'Brave is a privacy-focused Chromium browser with built-in tracker and advertisement blocking.'],
        'Opera' => ['category' => 'Browser', 'os' => 'Windows, macOS, Linux', 'slug' => 'opera', 'description' => 'Opera is a mainstream web browser with tab management, synchronization and built-in web tools.'],
        'WhatsApp' => ['category' => 'Communication', 'os' => 'Windows, macOS', 'slug' => 'whatsapp', 'description' => 'WhatsApp lets people send messages, make calls and share media across desktop and mobile devices.'],
        'Discord' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'discord', 'description' => 'Discord combines voice, video and text chat for communities, friends and gaming groups.'],
        'Telegram Desktop' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'telegram', 'description' => 'Telegram Desktop is a fast messaging client for chats, calls, groups and file sharing.'],
        'Spotify' => ['category' => 'Media', 'os' => 'Windows, macOS, Linux', 'slug' => 'spotify', 'description' => 'Spotify is a music and podcast streaming app with playlists, recommendations and offline listening for subscribers.'],
        'Netflix' => ['category' => 'Media', 'os' => 'Windows', 'slug' => 'netflix', 'description' => 'Netflix is a streaming service for films, series, documentaries and original programming.'],
        'YouTube' => ['category' => 'Media', 'os' => 'Windows, macOS, Linux', 'slug' => 'youtube', 'description' => 'YouTube is a video platform for watching creators, live streams, music and educational content.'],
        'Zoom' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'zoom', 'description' => 'Zoom provides video meetings, screen sharing, chat and webinars for personal and professional calls.'],
        'Microsoft Teams' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'microsoftteams', 'description' => 'Microsoft Teams provides chat, meetings, calls and collaboration for individuals, schools and organizations.'],
        'Skype' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'skype', 'description' => 'Skype provides voice and video calls, messaging and screen sharing between contacts.'],
        'Slack' => ['category' => 'Communication', 'os' => 'Windows, macOS, Linux', 'slug' => 'slack', 'description' => 'Slack organizes team conversations, direct messages, files and integrations into searchable channels.'],
        'Microsoft Word' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoftword', 'description' => 'Microsoft Word is a word processor for writing, editing, formatting and collaborating on documents.'],
        'Microsoft Excel' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoftexcel', 'description' => 'Microsoft Excel is a spreadsheet application for calculations, analysis, charts and data organization.'],
        'Microsoft PowerPoint' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoftpowerpoint', 'description' => 'Microsoft PowerPoint is a presentation tool for building slide decks with text, media, charts and animations.'],
        'Microsoft OneNote' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoftonenote', 'description' => 'Microsoft OneNote is a digital notebook for organizing notes, drawings, clippings and shared information.'],
        'Microsoft Outlook' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoftoutlook', 'description' => 'Microsoft Outlook combines email, calendars, contacts and tasks in one productivity application.'],
        'Microsoft 365' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'microsoft365', 'description' => 'Microsoft 365 is the subscription suite that brings Office apps, cloud storage and collaboration together.'],
        'Google Drive' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'googledrive', 'description' => 'Google Drive synchronizes files and folders with Google cloud storage and makes them available across devices.'],
        'Google Docs' => ['category' => 'Productivity', 'os' => 'Windows, macOS, Linux', 'slug' => 'googledocs', 'description' => 'Google Docs is a browser-based word processor with real-time collaboration and cloud saving.'],
        'Google Sheets' => ['category' => 'Productivity', 'os' => 'Windows, macOS, Linux', 'slug' => 'googlesheets', 'description' => 'Google Sheets is a collaborative online spreadsheet for calculations, tables, charts and shared work.'],
        'Dropbox' => ['category' => 'Productivity', 'os' => 'Windows, macOS, Linux', 'slug' => 'dropbox', 'description' => 'Dropbox synchronizes files, folders and shared workspaces across computers and mobile devices.'],
        'OneDrive' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'onedrive', 'description' => 'Microsoft OneDrive stores and synchronizes personal and work files with Microsoft cloud services.'],
        'Adobe Photoshop' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'adobephotoshop', 'description' => 'Adobe Photoshop is a professional image-editing application for retouching, compositing, graphics and digital art.'],
        'Adobe Illustrator' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'adobeillustrator', 'description' => 'Adobe Illustrator is a vector graphics application for logos, illustrations, icons and print artwork.'],
        'Adobe Premiere Pro' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'adobepremierepro', 'description' => 'Adobe Premiere Pro is a professional video editor for cutting, audio, effects, color and delivery.'],
        'Adobe Acrobat Reader' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'adobeacrobatreader', 'description' => 'Adobe Acrobat Reader is a free PDF viewer for reading, printing, signing and commenting on documents.'],
        'Adobe Lightroom' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'adobelightroom', 'description' => 'Adobe Lightroom organizes, edits and synchronizes photographs with non-destructive cloud-based tools.'],
        'Canva' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'canva', 'description' => 'Canva is a design platform for creating presentations, social graphics, documents, posters and videos.'],
        'DaVinci Resolve' => ['category' => 'Creative', 'os' => 'Windows, macOS, Linux', 'slug' => 'davinciresolve', 'description' => 'DaVinci Resolve combines video editing, visual effects, color grading and audio post-production.'],
        'CapCut' => ['category' => 'Creative', 'os' => 'Windows, macOS', 'slug' => 'capcut', 'description' => 'CapCut is a video editor for trimming clips, adding effects, captions, music and social-ready exports.'],
        'OBS Studio' => ['category' => 'Creative', 'os' => 'Windows, macOS, Linux', 'slug' => 'obsstudio', 'description' => 'OBS Studio is free recording and live-streaming software with scenes, sources, audio mixing and plugins.'],
        'VLC Media Player' => ['category' => 'Media', 'os' => 'Windows, macOS, Linux', 'slug' => 'vlc', 'description' => 'VLC Media Player is a free, open-source player that supports a wide range of video and audio formats.'],
        'Audacity' => ['category' => 'Creative', 'os' => 'Windows, macOS, Linux', 'slug' => 'audacity', 'description' => 'Audacity is a multitrack audio recorder and editor for recording, cleanup, mixing and exporting sound.'],
        'Steam' => ['category' => 'Gaming', 'os' => 'Windows, macOS, Linux', 'slug' => 'steam', 'description' => 'Steam is a digital game store and launcher with a large PC library, community features and cloud saves.'],
        'Epic Games Launcher' => ['category' => 'Gaming', 'os' => 'Windows, macOS', 'slug' => 'epicgames', 'description' => 'Epic Games Launcher is a PC storefront and launcher for Epic games and selected third-party titles.'],
        'Xbox App' => ['category' => 'Gaming', 'os' => 'Windows', 'slug' => 'xbox', 'description' => 'The Xbox app for PC provides access to PC Game Pass, Xbox games, friends, chat and game installs.'],
        'EA App' => ['category' => 'Gaming', 'os' => 'Windows, macOS', 'slug' => 'ea', 'description' => 'EA app is Electronic Arts desktop software for buying, installing and launching PC games.'],
        'ChatGPT' => ['category' => 'AI', 'os' => 'Windows, macOS', 'slug' => 'openai', 'description' => 'ChatGPT is an AI assistant for conversation, writing, analysis, coding and everyday research tasks.'],
        'Google Gemini' => ['category' => 'AI', 'os' => 'Windows, macOS, Linux', 'slug' => 'googlegemini', 'description' => 'Google Gemini is an AI assistant for questions, drafting, planning, analysis and multimodal work.'],
        'Microsoft Copilot' => ['category' => 'AI', 'os' => 'Windows, macOS, Linux', 'slug' => 'microsoftcopilot', 'description' => 'Microsoft Copilot is an AI assistant for answers, drafting, summaries and productivity workflows.'],
        '7-Zip' => ['category' => 'Utilities', 'os' => 'Windows', 'slug' => '7zip', 'description' => '7-Zip is a free file archiver for creating and extracting compressed files, including the efficient 7z format.'],
        'WinRAR' => ['category' => 'Utilities', 'os' => 'Windows', 'slug' => 'winrar', 'description' => 'WinRAR is a file archiver for creating, opening and managing RAR, ZIP and other compressed archives.'],
        'CCleaner' => ['category' => 'Utilities', 'os' => 'Windows, macOS', 'slug' => 'ccleaner', 'description' => 'CCleaner is a maintenance utility for managing temporary files, application clutter and selected startup items.'],
        'Notion' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'notion', 'description' => 'Notion combines notes, documents, databases and project planning in flexible workspaces.'],
        'Trello' => ['category' => 'Productivity', 'os' => 'Windows, macOS', 'slug' => 'trello', 'description' => 'Trello is a visual project-management tool built around boards, lists, cards and team collaboration.'],
        'Figma' => ['category' => 'Creative', 'os' => 'Windows, macOS, Linux', 'slug' => 'figma', 'description' => 'Figma is a collaborative interface-design and prototyping platform used in the browser and desktop app.']
    ];
    return $profiles[$title] ?? [];
}

function compatix_curated_app_screenshots(string $title): array {
    $sites = [
        'Google Chrome' => 'https://www.google.com/chrome/', 'Microsoft Edge' => 'https://www.microsoft.com/edge', 'Mozilla Firefox' => 'https://www.mozilla.org/firefox/', 'Brave' => 'https://brave.com/', 'Opera' => 'https://www.opera.com/',
        'WhatsApp' => 'https://www.whatsapp.com/download', 'Discord' => 'https://discord.com/', 'Telegram Desktop' => 'https://desktop.telegram.org/', 'Spotify' => 'https://www.spotify.com/', 'Netflix' => 'https://www.netflix.com/', 'YouTube' => 'https://www.youtube.com/', 'Zoom' => 'https://zoom.us/', 'Microsoft Teams' => 'https://www.microsoft.com/microsoft-teams/', 'Skype' => 'https://www.skype.com/', 'Slack' => 'https://slack.com/',
        'Microsoft Word' => 'https://www.microsoft.com/microsoft-365/word', 'Microsoft Excel' => 'https://www.microsoft.com/microsoft-365/excel', 'Microsoft PowerPoint' => 'https://www.microsoft.com/microsoft-365/powerpoint', 'Microsoft OneNote' => 'https://www.microsoft.com/microsoft-365/onenote', 'Microsoft Outlook' => 'https://www.microsoft.com/microsoft-365/outlook', 'Microsoft 365' => 'https://www.microsoft.com/microsoft-365', 'Google Drive' => 'https://www.google.com/drive/', 'Google Docs' => 'https://docs.google.com/', 'Google Sheets' => 'https://sheets.google.com/', 'Dropbox' => 'https://www.dropbox.com/', 'OneDrive' => 'https://www.microsoft.com/microsoft-365/onedrive/online-cloud-storage',
        'Adobe Photoshop' => 'https://www.adobe.com/products/photoshop.html', 'Adobe Illustrator' => 'https://www.adobe.com/products/illustrator.html', 'Adobe Premiere Pro' => 'https://www.adobe.com/products/premiere.html', 'Adobe Acrobat Reader' => 'https://www.adobe.com/acrobat/pdf-reader.html', 'Adobe Lightroom' => 'https://www.adobe.com/products/photoshop-lightroom.html', 'Canva' => 'https://www.canva.com/', 'DaVinci Resolve' => 'https://www.blackmagicdesign.com/products/davinciresolve', 'CapCut' => 'https://www.capcut.com/', 'OBS Studio' => 'https://obsproject.com/', 'VLC Media Player' => 'https://www.videolan.org/vlc/', 'Audacity' => 'https://www.audacityteam.org/',
        'Steam' => 'https://store.steampowered.com/about/', 'Epic Games Launcher' => 'https://store.epicgames.com/', 'Xbox App' => 'https://www.xbox.com/apps/xbox-app-for-pc', 'EA App' => 'https://www.ea.com/ea-app', 'ChatGPT' => 'https://chatgpt.com/', 'Google Gemini' => 'https://gemini.google.com/', 'Microsoft Copilot' => 'https://copilot.microsoft.com/', '7-Zip' => 'https://www.7-zip.org/', 'WinRAR' => 'https://www.win-rar.com/', 'CCleaner' => 'https://www.ccleaner.com/', 'Notion' => 'https://www.notion.so/', 'Trello' => 'https://trello.com/', 'Figma' => 'https://www.figma.com/'
    ];
    $site = $sites[$title] ?? 'https://www.google.com/search?q=' . rawurlencode($title . ' official app');
    $screenshots = [];
    foreach ([700, 900, 1100, 1300] as $height) {
        $screenshots[] = 'https://image.thum.io/get/width/1280/crop/' . $height . '/noanimate/' . $site;
    }
    return $screenshots;
}

