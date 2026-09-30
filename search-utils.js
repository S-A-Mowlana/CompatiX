/* Shared CompatiX title search and ranking. */
(function (global) {
  'use strict';

  const aliases = {
    'gta': 'grand theft auto v',
    'gta 5': 'grand theft auto v',
    'gta v': 'grand theft auto v',
    'cod': 'call of duty',
    'ac': 'assassin s creed',
    'csgo': 'counter strike',
    'cs go': 'counter strike',
    'cs 2': 'counter strike 2'
  };

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function levenshtein(left, right) {
    const a = String(left);
    const b = String(right);
    const row = Array.from({ length: b.length + 1 }, (_, index) => index);
    for (let i = 1; i <= a.length; i += 1) {
      let previous = row[0];
      row[0] = i;
      for (let j = 1; j <= b.length; j += 1) {
        const current = row[j];
        row[j] = Math.min(
          row[j] + 1,
          row[j - 1] + 1,
          previous + (a[i - 1] === b[j - 1] ? 0 : 1)
        );
        previous = current;
      }
    }
    return row[b.length];
  }

  function fuzzyMatch(query, name) {
    const queryWords = normalize(query).split(' ').filter(Boolean);
    const nameWords = normalize(name).split(' ').filter(Boolean);
    return queryWords.length > 0 && queryWords.every((queryWord) => {
      const limit = queryWord.length <= 4 ? 1 : 2;
      return nameWords.some((nameWord) => levenshtein(queryWord, nameWord) <= limit);
    });
  }

  function aliasMatch(query, name) {
    const target = aliases[normalize(query)];
    if (!target) return false;
    const normalizedName = normalize(name);
    return normalizedName === target || normalizedName.startsWith(`${target} `) || normalizedName.includes(target);
  }

  function escapeRegex(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function rank(query, items, limit = 8) {
    const normalizedQuery = normalize(query);
    if (!normalizedQuery) return [];
    const results = [];

    (Array.isArray(items) ? items : []).forEach((item, index) => {
      const name = String(item?.name || '').trim();
      const normalizedName = normalize(name);
      if (!normalizedName) return;

      let tier = Infinity;
      if (normalizedName === normalizedQuery) tier = 0;
      else if (normalizedName.startsWith(normalizedQuery)) tier = 1;
      else if (new RegExp(`(^|\\s)${escapeRegex(normalizedQuery)}(?=\\s|$)`).test(normalizedName)) tier = 2;
      else if (normalizedName.includes(normalizedQuery)) tier = 3;
      else if (fuzzyMatch(normalizedQuery, normalizedName)) tier = 4;
      else if (aliasMatch(normalizedQuery, normalizedName)) tier = 5;
      if (tier !== Infinity) results.push({ item, tier, index });
    });

    results.sort((left, right) => left.tier - right.tier || left.item.name.localeCompare(right.item.name) || left.index - right.index);
    return results.slice(0, limit).map((entry) => entry.item);
  }

  async function loadCatalog() {
    const [gamesResponse, appsResponse] = await Promise.all([
      fetch('games_cache.json'),
      fetch('apps_cache.json')
    ]);
    if (!gamesResponse.ok || !appsResponse.ok) throw new Error('Title catalog unavailable');
    const [games, apps] = await Promise.all([gamesResponse.json(), appsResponse.json()]);
    const gameRows = Array.isArray(games) ? games : (Array.isArray(games?.data) ? games.data : []);
    const appRows = Array.isArray(apps) ? apps : (Array.isArray(apps?.data) ? apps.data : []);
    return [
      ...gameRows.map((game) => ({
        name: game.name,
        type: 'game',
        cover: game.image_url || game.cover || game.background_image || '',
        category: game.genre || 'Game'
      })),
      ...appRows.map((app) => ({
        name: app.name,
        type: 'app',
        cover: app.icon_url || app.image_url || app.cover || '',
        category: app.category || 'App'
      }))
    ].filter((item) => item.name);
  }

  global.CompatiXSearch = { aliases, normalize, levenshtein, rank, loadCatalog };
}(window));
