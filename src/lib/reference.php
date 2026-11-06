<?php
// THUGSred Dorkify - reference data shown around the dork library:
// operator cheat sheet, combining patterns, cross-engine table, "Beyond Google" links.

// [operator, what it does, example, status: ok | flaky | dead]
function dorkify_operators(): array { return [
    ['site:', 'Limit to a domain, subdomain, path or whole TLD.', 'site:example.dk · site:*.example.dk · site:dk', 'ok'],
    ['-site:', 'Exclude a domain (or subdomain) from the results.', '"example.dk" -site:example.dk', 'ok'],
    ['inurl: · allinurl:', 'Word in the URL / every following word in the URL.', 'inurl:admin · allinurl:admin login', 'ok'],
    ['intitle: · allintitle:', 'Word or phrase in the page title / every following word.', 'intitle:"index of"', 'ok'],
    ['intext: · allintext:', 'Word or phrase in the body text.', 'intext:"DB_PASSWORD"', 'ok'],
    ['filetype: · ext:', 'File extension. The two are interchangeable.', 'ext:sql · filetype:pdf', 'ok'],
    ['"…"', 'Exact phrase. Also forces a single word to be matched as written.', '"internal use only"', 'ok'],
    ['-term', 'Exclude a word, phrase or operator.', '-inurl:blog -"press release"', 'ok'],
    ['OR · |', 'Either term. Must be uppercase OR. Binds tighter than AND.', '(ext:sql OR ext:bak)', 'ok'],
    ['( )', 'Group terms - always bracket an OR list.', 'site:example.dk (inurl:dev OR inurl:test)', 'ok'],
    ['*', 'Wildcard for one or more whole words, inside quotes.', '"password is *"', 'ok'],
    ['after: · before:', 'Date limit (YYYY-MM-DD), based on Google\'s own dating of the page.', 'site:example.dk after:2026-01-01', 'ok'],
    ['X..Y', 'Number range.', '"invoice" 2019..2023', 'ok'],
    ['AROUND(n)', 'Two terms within n words of each other. Uppercase.', '"admin" AROUND(3) "password"', 'flaky'],
    ['inanchor:', 'Words in the link text pointing at the page.', 'inanchor:"staff login"', 'flaky'],
    ['related:', 'Sites similar to a domain. Removed from the docs in 2023.', 'related:example.dk', 'dead'],
    ['cache:', 'Google\'s cached copy. Retired in 2024 - use web.archive.org instead.', 'cache:example.dk', 'dead'],
    ['info: · link:', 'Page info / pages linking to a URL.', 'link:example.dk', 'dead'],
    ['+term · ~term', 'Force a word / include synonyms. Use "quotes" instead of +.', '+admin', 'dead'],
]; }

// [title, explanation, example]
function dorkify_patterns(): array { return [
    ['Stack to narrow', 'A space means AND. Every operator you add narrows the set, so start broad and add one at a time.', 'site:example.dk ext:pdf intext:"confidential"'],
    ['Always bracket OR', 'OR binds tighter than the implicit AND: a b OR c d means a (b OR c) d. Brackets make it say what you mean.', 'site:example.dk (ext:sql OR ext:bak) "INSERT INTO"'],
    ['Subtract the noise', 'Exclude what you have already reviewed, then rerun. -site: works on subdomains and paths too.', 'site:example.dk -site:www.example.dk -inurl:blog -inurl:news'],
    ['Find yourself elsewhere', 'Quote the domain and exclude your own site to see everything third parties have about you.', '"example.dk" -site:example.dk'],
    ['Type + content', 'The file type narrows the set, a content word finds the sensitive one in it.', 'site:example.dk ext:xlsx intext:"cpr"'],
    ['Listing + filename', 'Directory listings have the title "Index of /…". Add the filename you are afraid of.', 'site:example.dk intitle:"index of" ".env"'],
    ['Wildcards inside phrases', '* stands in for whole words, and only inside quotes. It does not match partial words.', 'site:example.dk "password * admin"'],
    ['What is new since the last audit', 'after: limits to pages Google dated after a day - rerun your audit with the date of the previous one.', 'site:example.dk ext:pdf after:2026-10-01'],
    ['Don\'t mix allin* with other operators', 'allintitle: takes every following word as a title word. Use one intitle: per word when combining.', 'intitle:login intitle:admin site:example.dk'],
    ['Quote exact tokens', 'Quotes stop Google splitting or stemming tokens like DB_PASSWORD, wp-config or AKIA.', 'site:example.dk "DB_PASSWORD"'],
    ['Know the limits', 'Max 32 words, no space after the colon, case-insensitive (except OR and AROUND), and result counts are estimates. The Google buttons here add &filter=0 so near-duplicates are not hidden.', 'intitle: "index of"  ✗   intitle:"index of"  ✓'],
    ['Fresh leaks first', 'New exposures are the ones nobody has fixed yet. Date-bound with after: - it works on paste sites too.', 'site:pastebin.com "example.dk" after:2026-10-01'],
    ['Search logged out, pace yourself', 'Signed-in results are personalised - use a private window. Firing many dorks fast triggers CAPTCHAs; pause or switch engine.', '"@example.dk" -site:example.dk -site:linkedin.com'],
    ['Try another engine','Bing and DuckDuckGo index differently and also understand site:, filetype: and intitle:. Run the high-severity ones there too.', 'site:example.dk filetype:sql'],
]; }
// Cross-engine operator equivalents: [goal, Google, Bing, DuckDuckGo, Yandex]. "–" = no operator.
function dorkify_engines(): array { return [
    ['domain', 'site:', 'site:', 'site:', 'site: · host: · rhost:'],
    ['title', 'intitle:', 'intitle:', 'intitle:', 'title:'],
    ['URL', 'inurl:', 'inurl: (unofficial)', 'inurl:', 'url: · inurl:'],
    ['body text', 'intext:', 'inbody:', '–', '–'],
    ['file type', 'filetype: · ext:', 'filetype: · ext:', 'filetype:', 'mime:'],
    ['exact · exclude', '"…" · -', '"…" · -', '"…" · -', '"…" · -'],
    ['either', 'OR · |', 'OR · |', 'OR', '|'],
    ['date', 'after: · before:', '– (date filter in UI)', '– (date filter in UI)', 'date:'],
    ['hosted on IP', '–', 'ip:', '–', '–'],
    ['RSS feeds', '–', 'feed: · hasfeed:', '–', '–'],
    ['language', '– (lr= URL param)', 'language:', '–', 'lang:'],
]; }

// "Beyond Google" links: [title, what, URL template ({d} domain, {user} username), note]
function dorkify_beyond(): array { return [
    ['crt.sh', 'Every TLS certificate issued for the domain - the most complete subdomain list, internal names included.', 'https://crt.sh/?q=%25.{d}', ''],
    ['Wayback: all archived URLs', 'Every URL the Internet Archive has captured on the domain and its subdomains (plain text).', 'https://web.archive.org/cdx/search/cdx?url={d}&matchType=domain&output=text&fl=original&collapse=urlkey', 'Can be very long for big sites.'],
    ['Wayback: archived sensitive files', 'Same list, filtered to config, dump, backup, key and spreadsheet extensions.', 'https://web.archive.org/cdx/search/cdx?url={d}&matchType=domain&output=text&fl=original&collapse=urlkey&filter=original:.*%5C.(env%7Csql%7Cbak%7Cold%7Czip%7Ctar%7Cgz%7Clog%7Cconf%7Cconfig%7Cini%7Cyml%7Cyaml%7Cpem%7Ckey%7Cpst%7Ccsv%7Cxlsx)$', 'Hits here need an exclusion request to info@archive.org.'],
    ['Wayback: browse', 'Browse captures by URL prefix.', 'https://web.archive.org/web/*/{d}/*', ''],
    ['GitHub: domain + secrets', 'Code mentioning the domain next to secret-looking words.', 'https://github.com/search?type=code&q=%22{d}%22+(password+OR+secret+OR+token+OR+apikey)', 'Code search needs a GitHub login.'],
    ['GitHub: .env files', '.env files anywhere on GitHub that mention the domain.', 'https://github.com/search?type=code&q=%22{d}%22+path%3A.env', 'Code search needs a GitHub login.'],
    ['GitHub: user\'s repositories', 'Public repositories of the username in the scope bar.', 'https://github.com/{user}?tab=repositories', ''],
    ['Shodan: hostname', 'Internet-facing services Shodan has seen on the domain.', 'https://www.shodan.io/search?query=hostname%3A{d}', 'Filters need a (free) Shodan account.'],
    ['Shodan: certificates', 'Hosts presenting a certificate for the domain - finds servers outside your DNS.', 'https://www.shodan.io/search?query=ssl.cert.subject.cn%3A{d}', 'Filters need a (free) Shodan account.'],
    ['Google Safe Browsing status', 'Is the domain flagged as malicious or compromised?', 'https://transparencyreport.google.com/safe-browsing/search?url={d}', ''],
    ['Have I Been Pwned: domain search', 'Breaches containing addresses on your domain.', 'https://haveibeenpwned.com/DomainSearch', 'You verify domain ownership first.'],
]; }
