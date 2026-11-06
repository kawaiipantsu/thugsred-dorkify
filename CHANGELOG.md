# Changelog

## 1.0.0 — 2026-11-05

First standalone release of THUGSred's self-hosted Google dork toolkit.

- 154 dorks in 11 categories, each with severity, what it finds and how to fix it.
- Scope bar (domain, organisation, person, username) fills every dork in; scope lives in the URL and `localStorage` only.
- Query builder with correctly bracketed OR groups, word-limit warning, and one-click search on Google, Bing, DuckDuckGo, Brave and Yandex.
- Operator cheat sheet (works / unreliable / gone), cross-engine operator table, 14 combining patterns.
- "Beyond Google" links: crt.sh, Wayback CDX (incl. archived sensitive files), GitHub code search, Shodan, Safe Browsing, HIBP.
- Fix & remove workflow with Google/Bing/Internet Archive removal routes and Apache/nginx snippets.
- Contents sidebar with scrollspy; wraps to chips on small screens.
- Mascot, logo and the "increasingly ridiculous searches" typer.
- Docker image (PHP 8.4 + Apache, non-root, read-only, strict CSP), static export, dork linter.
