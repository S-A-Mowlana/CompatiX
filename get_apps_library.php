<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
require_once __DIR__ . '/curated_catalog.php';
function cx_app_source(): array {$file=__DIR__.'/apps_cache.json';$json=is_file($file)?json_decode((string)file_get_contents($file),true):[];$out=[];foreach((array)($json['data']??[])as$row)if(!empty($row['name']))$out[compatix_curated_normalize($row['name'])]=$row;return $out;}
function cx_app_brand_slug(string $name, array $profile = []): string {
	$aliases = [
		'Google Chrome'=>'googlechrome', 'Microsoft Edge'=>'microsoftedge', 'Mozilla Firefox'=>'firefox', 'Telegram Desktop'=>'telegram',
		'Microsoft Teams'=>'microsoftteams', 'Microsoft Word'=>'microsoftword', 'Microsoft Excel'=>'microsoftexcel', 'Microsoft PowerPoint'=>'microsoftpowerpoint',
		'Microsoft OneNote'=>'microsoftonenote', 'Microsoft Outlook'=>'microsoftoutlook', 'Microsoft 365'=>'microsoft365', 'Google Drive'=>'googledrive',
		'Google Docs'=>'googledocs', 'Google Sheets'=>'googlesheets', 'Google Slides'=>'googleslides', 'Adobe Photoshop'=>'adobephotoshop',
		'Adobe Illustrator'=>'adobeillustrator', 'Adobe Premiere Pro'=>'adobepremierepro', 'Adobe Acrobat Reader'=>'adobeacrobatreader',
		'Adobe Acrobat Pro'=>'adobeacrobatreader', 'DaVinci Resolve'=>'davinciresolve', 'DaVinci Resolve Studio'=>'davinciresolve', 'OBS Studio'=>'obsstudio',
		'VLC Media Player'=>'vlcmediplayer', '7-Zip'=>'7zip', 'WinRAR'=>'winrar', 'Microsoft Visual C++'=>'visualstudio', '.NET SDK'=>'dotnet',
		'Node.js'=>'nodedotjs', 'npm'=>'npm', 'qBittorrent'=>'qbittorrent', 'µTorrent'=>'utorrent', 'ÂµTorrent'=>'utorrent',
		'YouTube'=>'youtube', 'ChatGPT'=>'openai', 'Google Gemini'=>'googlegemini', 'Microsoft Copilot'=>'microsoftcopilot', 'PowerShell'=>'powershell',
		'Windows Terminal'=>'windowsterminal', 'Windows Security'=>'windows11', 'Microsoft Defender'=>'microsoftdefender', 'Xbox App'=>'xbox',
		'Xbox Cloud Gaming'=>'xbox', 'EA App'=>'ea', 'Epic Games Launcher'=>'epicgames', 'Riot Client'=>'riotgames', 'Ubisoft Connect'=>'ubisoft',
		'Rockstar Games Launcher'=>'rockstargames', 'NVIDIA App'=>'nvidia', 'AMD Software: Adrenalin Edition'=>'amd', 'Intel Graphics Command Center'=>'intel',
		'Google Earth'=>'googleearth', 'Google Earth Pro'=>'googleearth', 'Apple Music'=>'applemusic', 'Windows Media Player'=>'windows',
		'Visual Studio Code'=>'visualstudiocode', 'Visual Studio'=>'visualstudio', 'JetBrains IntelliJ IDEA'=>'intellijidea', 'JetBrains PyCharm'=>'pycharm',
		'JetBrains PhpStorm'=>'phpstorm', 'JetBrains WebStorm'=>'webstorm', 'JetBrains CLion'=>'clion', 'JetBrains Rider'=>'rider',
		'Android Studio'=>'androidstudio', 'GitHub Desktop'=>'github', 'Docker Desktop'=>'docker', 'GitKraken'=>'gitkraken', 'Unity Hub'=>'unity',
		'Unity Editor'=>'unity', 'Unreal Engine'=>'unrealengine', 'Blender'=>'blender', 'Figma'=>'figma', 'Canva'=>'canva', 'Notion'=>'notion',
		'Bitwarden'=>'bitwarden', '1Password'=>'1password', 'KeePassXC'=>'keepassxc', 'Mozilla Thunderbird'=>'thunderbird', 'Thunderbird'=>'thunderbird',
		'NordVPN'=>'nordvpn', 'ExpressVPN'=>'expressvpn', 'Proton VPN'=>'protonvpn', 'Surfshark'=>'surfshark', 'WireGuard'=>'wireguard',
		'Steam'=>'steam', 'Steam Link'=>'steam', 'Discord'=>'discord', 'Spotify'=>'spotify', 'Slack'=>'slack', 'Zoom'=>'zoom', 'Skype'=>'skype',
		'WhatsApp'=>'whatsapp', 'Signal Desktop'=>'signal', 'Dropbox'=>'dropbox', 'OneDrive'=>'onedrive', 'iCloud'=>'icloud', 'Opera GX'=>'operagx',
	];
	$slug = $aliases[$name] ?? ($profile['slug'] ?? '');
	if ($slug === '') $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $name));
	return trim($slug);
}
function cx_app_image(string $name, array $profile, array $old): string {
	$slug = cx_app_brand_slug($name, $profile);
	$local = 'assets/icons/apps/' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) . '.svg';
	// The local SVGs are safe fallbacks, but many are initials placeholders.
	// Prefer the real brand mark for every app when a Simple Icons slug exists.
	if ($slug !== '') return 'https://cdn.simpleicons.org/' . rawurlencode($slug);
	return (string)($old['icon_url'] ?? $local) ?: $local;
}
function cx_app_fallback_image(string $name): string {
	return 'assets/icons/apps/' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) . '.svg';
}
function cx_apps(): array {
	$source = cx_app_source();
	$items = [];
	foreach (compatix_curated_app_titles() as $position => $name) {
		$old = $source[compatix_curated_normalize($name)] ?? [];
		$profile = compatix_curated_app_profile($name);
		$category = $profile['category'] ?? compatix_curated_app_category($name);
		$os = $profile['os'] ?? (($old['supported_os'] ?? '') === 'See publisher support page' ? 'Windows' : ($old['supported_os'] ?? 'Windows'));
		$description = $profile['description'] ?? ($old['full_description'] ?? ($name . ' is a desktop application in the CompatiX curated library.'));
		$minRam = (int)($old['min_ram'] ?? 0) ?: 4;
		$minStorage = (int)($old['min_storage'] ?? 0) ?: 2;
		$recRam = (int)($old['rec_ram'] ?? 0) ?: max(8, $minRam);
		$recStorage = (int)($old['rec_storage'] ?? 0) ?: max(4, $minStorage * 2);
		$screenshots = !empty($old['screenshots']) ? $old['screenshots'] : compatix_curated_app_screenshots($name);
		$imageUrl = cx_app_image($name, $profile, $old);
		$items[] = array_merge($old, [
			'id' => (string)($position + 1),
			'name' => $name,
			'category' => $category,
			'version_info' => 'Current stable release',
			'supported_os' => $os,
			'full_description' => $description,
			'short_description' => $description,
			'tagline' => $description,
			'image_url' => $imageUrl,
			'icon_url' => $imageUrl,
			'icon_fallback' => cx_app_fallback_image($name),
			'cover' => $imageUrl,
			'screenshots' => $screenshots,
			'min_cpu' => $old['min_cpu'] ?? '64-bit dual-core processor, 2 GHz or better',
			'min_gpu' => $old['min_gpu'] ?? 'Integrated graphics with current drivers',
			'min_ram' => $minRam,
			'min_storage' => $minStorage,
			'rec_cpu' => $old['rec_cpu'] ?? '64-bit quad-core processor, 2.5 GHz or better',
			'rec_gpu' => $old['rec_gpu'] ?? 'Modern integrated or entry-level dedicated graphics',
			'rec_ram' => $recRam,
			'rec_storage' => $recStorage,
			'performance_tier' => $old['performance_tier'] ?? 'Balanced',
			'requirements_known' => true,
			'source' => $old['source'] ?? 'Official application requirements',
			'ai_estimated' => (bool)($old['ai_estimated'] ?? false)
		]);
	}
	return $items;
}
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
$id=preg_replace('/\D/','',(string)($_GET['id']??''));$apps=cx_apps();if($id!==''){foreach($apps as$app)if($app['id']===$id){echo json_encode($app,JSON_UNESCAPED_SLASHES);exit;}http_response_code(404);echo json_encode(['error'=>'App not found']);exit;}
$categories=array_filter(array_map('trim',explode(',',(string)($_GET['category']??''))));$oses=array_filter(array_map('trim',explode(',',(string)($_GET['os']??''))));$ram=(int)($_GET['min_ram']??0);$storage=(int)($_GET['min_storage']??0);$tier=trim((string)($_GET['performance_tier']??''));$query=compatix_curated_normalize((string)($_GET['search']??''));$apps=array_values(array_filter($apps,function($app)use($categories,$oses,$ram,$storage,$tier,$query){$hay=compatix_curated_normalize($app['name'].' '.$app['category'].' '.$app['full_description']);if($query!==''&&!str_contains($hay,$query))return false;if($categories&&!in_array($app['category'],$categories,true))return false;if($oses&&!array_intersect($oses,array_map('trim',explode(',',(string)$app['supported_os']))))return false;if($ram>0&&(int)$app['min_ram']<$ram)return false;if($storage>0&&(int)$app['min_storage']<$storage)return false;return !($tier!==''&&$app['performance_tier']!==$tier);}));$sort=(string)($_GET['sort']??'name');usort($apps,fn($a,$b)=>$sort==='ram'?((int)$a['min_ram']<=>(int)$b['min_ram']):($sort==='storage'?((int)$a['min_storage']<=>(int)$b['min_storage']):strcasecmp($a['name'],$b['name'])));echo json_encode($apps,JSON_UNESCAPED_SLASHES);
}
