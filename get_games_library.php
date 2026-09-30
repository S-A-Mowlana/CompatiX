<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/curated_catalog.php';

function cx_game_source(): array {
    $file = __DIR__ . '/games_cache.json';
    $json = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
    $rows = is_array($json['data'] ?? null) ? $json['data'] : [];
    $index = [];
    foreach ($rows as $row) if (!empty($row['name'])) $index[compatix_curated_normalize($row['name'])] = $row;
    return $index;
}
function cx_game_dedupe_strings(array $items): array {
    $seen = []; $out = [];
    foreach ($items as $item) {
        $clean = trim((string)$item);
        $key = strtolower($clean);
        if ($clean !== '' && !isset($seen[$key])) {
            $seen[$key] = true;
            $out[] = $clean;
        }
    }
    return $out;
}
function cx_game_unknown_requirements(): array {
    return [
        'min_cpu' => 'Intel Core i5-8400 / AMD Ryzen 5 2600',
        'min_gpu' => 'NVIDIA GTX 1050 Ti / AMD RX 570',
        'min_ram' => 8,
        'min_storage' => 20,
        'rec_cpu' => 'Intel Core i7-9700 / AMD Ryzen 7 3700X',
        'rec_gpu' => 'NVIDIA RTX 2060 / AMD RX 5700',
        'rec_ram' => 16,
        'rec_storage' => 20,
        'ai_estimated' => true,
        'source' => 'Specter AI'
    ];
}
function cx_game_cover(string $name, string $fallback): string {
    $known = [
        'Minecraft' => 'https://upload.wikimedia.org/wikipedia/en/5/51/Minecraft_cover.png',
        'Fortnite' => 'https://upload.wikimedia.org/wikipedia/en/0/09/FortniteSaveTheWorld.jpg',
        'League of Legends' => 'https://en.wikipedia.org/wiki/Special:FilePath/League%20of%20Legends%20cover.jpg',
        'Valorant' => 'https://en.wikipedia.org/wiki/Special:FilePath/Valorant%20cover.jpg',
    ];
    if (isset($known[$name])) return $known[$name];
    if ($fallback !== '') return $fallback;
    return 'https://en.wikipedia.org/wiki/Special:FilePath/' . rawurlencode($name . ' cover.jpg');
}
function cx_games(): array {
    $source=cx_game_source(); $items=[];
    foreach (compatix_curated_game_titles() as $position=>$name) {
        $old=$source[compatix_curated_normalize($name)]??[];
        $canonical = compatix_canonical_requirements($name);
        $has_req = !empty($old['min_cpu']) && !preg_match('/not published|not specified|contact support/i', (string)$old['min_cpu']);
        $req = $canonical ?: ($has_req ? $old : cx_game_unknown_requirements());
        $img = cx_game_cover($name, (!empty($old['image_url']) && strpos($old['image_url'], 'placeholder') === false) ? $old['image_url'] : '');
        $genre = $old['genre'] ?? compatix_curated_game_genre($name);
        $genres = compatix_curated_game_genres($genre, (array)($old['genres'] ?? []));
        $tags = compatix_curated_game_genres($genre, (array)($old['tags'] ?? []));
        $desc = $old['full_description'] ?? ($name . ' is an immersive title featuring expansive gameplay, detailed environments, and high-performance PC compatibility.');
        $shots = array_values(array_filter((array)($old['short_screenshots'] ?? []), 'is_string'));
        $ai_est = isset($old['ai_estimated']) ? (bool)$old['ai_estimated'] : !$has_req;

        $items[] = array_merge($req, [
            'id' => (string)($position + 1),
            'name' => $name,
            'genre' => $genre,
            'genres' => $genres,
            'tags' => $tags,
            'supported_os' => $old['supported_os'] ?? 'Windows 10 64-bit',
            'platforms' => $old['platforms'] ?? ['Windows'],
            'full_description' => $desc,
            'short_description' => $old['short_description'] ?? $desc,
            'tagline' => $old['tagline'] ?? $desc,
            'image_url' => $img,
            'short_screenshots' => $shots,
            'released' => $old['released'] ?? (compatix_curated_game_year($name) . '-01-01'),
            'year' => (int)($old['year'] ?? compatix_curated_game_year($name)),
            'rating' => $old['rating'] ?? null,
            'performance_tier' => $old['performance_tier'] ?? 'Balanced',
            'requirements_known' => true,
            'source' => $old['source'] ?? ($ai_est ? 'Specter AI' : 'RAWG'),
            'ai_estimated' => $ai_est
        ]);
    } return $items;
}
function cx_game_matches(array $item,array $genres,array $oses,int $ram,int $storage,string $tier,string $search): bool {
    $hay=compatix_curated_normalize($item['name'].' '.($item['genre']??'').' '.implode(' ',(array)($item['tags']??[])).' '.($item['full_description']??''));$query=compatix_curated_normalize($search);
    if($query!==''&&!str_contains($hay,$query))return false;if($genres&&!array_intersect($genres,(array)($item['genres']??[$item['genre']??''])))return false;if($oses&&!array_intersect($oses,array_map('trim',explode(',',(string)$item['supported_os']))))return false;if($ram>0&&(int)$item['min_ram']<$ram)return false;if($storage>0&&(int)$item['min_storage']<$storage)return false;return !($tier!==''&&$item['performance_tier']!==$tier);
}
$id=preg_replace('/\D/','',(string)($_GET['id']??''));$games=cx_games();
if($id!==''){foreach($games as $game)if($game['id']===$id){echo json_encode($game,JSON_UNESCAPED_SLASHES);exit;}http_response_code(404);echo json_encode(['error'=>'Game not found']);exit;}
$genres=array_filter(array_map('trim',explode(',',(string)($_GET['genre']??''))));$oses=array_filter(array_map('trim',explode(',',(string)($_GET['os']??''))));$ram=(int)($_GET['min_ram']??0);$storage=(int)($_GET['min_storage']??0);$tier=trim((string)($_GET['performance_tier']??''));$search=(string)($_GET['search']??'');$games=array_values(array_filter($games,fn($game)=>cx_game_matches($game,$genres,$oses,$ram,$storage,$tier,$search)));
$sort=(string)($_GET['sort']??'name');usort($games,fn($a,$b)=>$sort==='ram'?((int)$a['min_ram']<=>(int)$b['min_ram']):($sort==='storage'?((int)$a['min_storage']<=>(int)$b['min_storage']):strcasecmp($a['name'],$b['name'])));echo json_encode($games,JSON_UNESCAPED_SLASHES);
