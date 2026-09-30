# CompatiX

CompatiX is a browser-based PC compatibility checker for games and desktop applications. Enter your hardware, choose a title, and get a quick comparison against its minimum requirements before installing it.

Live site: [s-a-mowlana.github.io/CompatiX](https://s-a-mowlana.github.io/CompatiX/)

## Features

- PC compatibility checks for games and applications
- Manual or browser-assisted hardware specification entry
- Curated games and applications libraries
- Search, filtering, requirement summaries, and detail pages
- Build Advisor for hardware recommendations
- Specter AI assistant for compatibility questions and upgrade guidance
- Responsive dark/light interface with no account required

## Pages

| Page | Purpose |
| --- | --- |
| `index.html` | Landing page and quick Specter prompts |
| `check.html` | Enter PC specifications and run a compatibility check |
| `results.html` | View the comparison and suggested upgrades |
| `games-library.html` | Browse the games catalog |
| `apps-library.html` | Browse the applications catalog |
| `game-details.html` | View game requirements and media |
| `app-details.html` | View application requirements and media |
| `build-advisor.html` | Generate a hardware build suggestion |
| `specter.html` | Chat with the Specter assistant |

## Technology

- HTML5 and CSS3
- Vanilla JavaScript
- PHP endpoints for traditional server deployments
- JSON catalogs for static hosting
- RAWG for game metadata during catalog generation
- Groq for Specter AI responses

## Running locally

### Static mode

The front end can be served by any static web server:

```bash
python -m http.server 8000
```

Then open <http://localhost:8000/>.

Static mode uses the committed JSON catalogs and does not execute the PHP files. This is the mode required by GitHub Pages.

### PHP mode

For the server-backed features, use PHP with cURL enabled and configure the required environment variables:

```env
RAWG_API_KEY=your_rawg_key
GROQ_API_KEY=your_groq_key
```

Do not commit `.env` or `config.php`. The PHP endpoints include the compatibility checker, catalog lookup, requirements lookup, contact form, and Specter backend.

## Static deployment notes

GitHub Pages serves files but does not execute PHP. Before deploying the static version:

1. Ensure `apps_cache.json`, `games_cache.json`, `titles.json`, and `parts.json` are committed.
2. Ensure the complete `assets/` directory is committed, including the logo and Specter avatar files.
3. Keep browser requests pointed at `.json` files rather than `.php` endpoints.
4. Do not expose server-only PHP configuration or API credentials.

The static Specter implementation calls Groq directly from the browser. This makes the API key visible to anyone who can inspect the deployed JavaScript. For a production deployment, replace this with a serverless proxy such as a Cloudflare Worker or Vercel Edge Function that stores the key server-side.

## Data and accuracy

Game metadata is sourced from RAWG when the catalog is generated. Application entries are curated manually. Requirements may vary by version, operating system, graphics settings, drivers, and background workloads, so CompatiX results should be treated as practical guidance rather than a guarantee of performance.

## Project structure

```text
assets/              Images, icons, logos, and Specter artwork
*.html               Public pages
script.js            Shared front-end behavior
search-utils.js      Catalog search and ranking helpers
style.css            Shared styling
*_cache.json         Static catalog data
titles.json          Compatibility requirement overrides
*.php                Server-side endpoints and catalog tooling
admin/               Catalog and image maintenance scripts
```

## Credits

Created by **S A Mowlana**.

## License

No license has been specified yet. Add a `LICENSE` file before accepting reuse or external contributions.
