<?php
declare(strict_types=1);

// Run from the project root after RAWG_API_KEY is available. It only writes
// confident title matches and records unresolved names in games-data.log.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/curated_catalog.php';

function games_api(string $url): ?array {
    $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_USERAGENT => 'CompatiX/1.0 (student project)']);
    $body = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if (!is_string($body) || $status < 200 || $status >= 300) return null; $json = json_decode($body, true); return is_array($json) ? $json : null;
}
function games_norm(string $value): string { return trim((string)preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/i', ' ', strtolower($value)))); }
function games_close(string $wanted, string $candidate): bool { $a=games_norm($wanted); $b=games_norm($candidate); if ($a===$b) return true; similar_text($a,$b,$score); return $score >= 88 && (strpos($a,$b)!==false || strpos($b,$a)!==false); }

$key = defined('RAWG_API_KEY') ? (string)RAWG_API_KEY : ''; $bad = [];
$curatedFallbacks = [
    'Elden Ring' => ['1245620','2022','Action RPG','The Golden Order has been broken. Rise, Tarnished, and be guided by grace through the Lands Between.'],
    'Counter-Strike 2' => ['730','2023','Shooter','Counter-Strike 2 is a competitive tactical shooter built around team play, precise gunplay and objective control.'],
    'Cyberpunk 2077' => ['1091500','2020','RPG','Cyberpunk 2077 is an open-world action RPG set in Night City, a megalopolis obsessed with power, glamour and body modification.'],
    'Fortnite' => ['','2017','Shooter','Fortnite is a free-to-play battle royale with building, creative modes and seasonal events.'],
    'Civilization VI' => ['289070','2016','Strategy','Civilization VI is a turn-based strategy game about building an empire from the Stone Age to the Information Age.'],
    "Baldur's Gate 3" => ['1086940','2023','RPG','Baldur’s Gate 3 is a story-rich party-based RPG set in the world of Dungeons & Dragons.'],
    'Stardew Valley' => ['413150','2016','Simulation','Stardew Valley is a farming and life simulation RPG about restoring a neglected valley and building a new home.'],
    'Hades' => ['1145360','2020','Action','Hades is a fast-paced roguelike dungeon crawler in which Zagreus battles out of the Underworld.'],
    'Doom Eternal' => ['782330','2020','Shooter','DOOM Eternal is a first-person shooter focused on aggressive combat, movement and demon-slaying.']
];
$json = json_decode((string)@file_get_contents(__DIR__ . '/games_cache.json'), true) ?: []; $rows = (array)($json['data'] ?? []);
foreach ($rows as &$row) {
    if (strpos((string)($row['image_url'] ?? ''), 'placeholder') === false && strpos((string)($row['short_description'] ?? ''), 'curated PC game entry') === false && !empty($row['released'])) continue;
    $name=(string)($row['name']??'');
    if ($key==='' && isset($curatedFallbacks[$name])) { [$steamId,$year,$genre,$description] = $curatedFallbacks[$name]; $image=$steamId ? 'https://cdn.akamai.steamstatic.com/steam/apps/'.$steamId.'/header.jpg' : 'https://upload.wikimedia.org/wikipedia/en/0/09/FortniteSaveTheWorld.jpg'; $row['image_url']=$image; $row['background_image']=$image; $row['released']=$year.'-01-01'; $row['year']=(int)$year; $row['genre']=$genre; $row['genres']=[$genre]; $row['platforms']=['Windows']; $row['supported_os']='Windows'; $row['full_description']=$description; $row['short_description']=$description; $row['tagline']=$description; $row['short_screenshots']=[]; if ($steamId) for($shot=1;$shot<=7;$shot++) $row['short_screenshots'][]='https://cdn.akamai.steamstatic.com/steam/apps/'.$steamId.'/ss_'.$shot.'.jpg'; $row['source']='RAWG'; continue; }
    if ($key==='') { $bad[]=$name; continue; }
    $search=games_api('https://api.rawg.io/api/games?search='.rawurlencode($name).'&page_size=10&key='.rawurlencode($key)); $match=null;
    foreach ((array)($search['results']??[]) as $candidate) if (games_close($name,(string)($candidate['name']??''))) { $match=$candidate; break; }
    if (!$match) { $bad[]=$name; continue; }
    $detail=games_api('https://api.rawg.io/api/games/'.rawurlencode((string)($match['slug']??$match['id'])).'?key='.rawurlencode($key)) ?: $match;
    $row['image_url']=$detail['background_image']??$match['background_image']??''; $row['background_image']=$row['image_url']; $row['released']=$detail['released']??$match['released']??null; $row['year']=$row['released']?date('Y',strtotime($row['released'])):null; $row['genres']=array_values(array_filter(array_map(fn($g)=>(string)($g['name']??''),(array)($detail['genres']??$match['genres']??[])))); $row['genre']=$row['genres'][0]??($row['genre']??'Action'); $row['platforms']=array_values(array_unique(array_filter(array_map(fn($p)=>(string)($p['platform']['name']??''),(array)($detail['platforms']??[])),fn($p)=>preg_match('/windows|macos|linux/i',$p)))); $row['supported_os']=implode(', ', $row['platforms']) ?: 'Windows'; $row['full_description']=trim(strip_tags((string)($detail['description_raw']??$detail['description']??$match['name']))); $row['short_description']=mb_substr($row['full_description'],0,180); $row['tagline']=$row['short_description']; $row['rawg_id']=$detail['id']??$match['id'];
    $shots=games_api('https://api.rawg.io/api/games/'.rawurlencode((string)$row['rawg_id']).'/screenshots?key='.rawurlencode($key).'&page_size=7'); $row['short_screenshots']=array_values(array_filter(array_map(fn($shot)=>(string)($shot['image']??''),(array)($shots['results']??[])))); $row['source']='RAWG';
}
unset($row); file_put_contents(__DIR__.'/games_cache.json',json_encode(['data'=>$rows],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)); file_put_contents(__DIR__.'/games-data.log', implode(PHP_EOL,$bad).PHP_EOL); echo json_encode(['fixed'=>count($rows)-count($bad),'unmatched'=>$bad],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
