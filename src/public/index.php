<?php
// THUGSred Dorkify - Google dork reference, query builder and exposure-audit
// workflow. Rendered by PHP (Docker / php -S) or exported to static HTML by
// scripts/build-static.sh. All behaviour is client-side in assets/dorkify.js.
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/dorks.php';
require_once __DIR__ . '/../lib/reference.php';
if (PHP_SAPI !== 'cli') header('X-Content-Type-Options: nosniff');

$lib      = dork_library();
$count    = dork_count();
$OPS      = dorkify_operators();
$PATTERNS = dorkify_patterns();
$ENGINES  = dorkify_engines();
$BEYOND   = dorkify_beyond();
$e        = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES);
$glyph    = fn(int $cp): string => '&#x' . dechex($cp) . ';';
$SEV      = ['crit' => 'critical', 'high' => 'high', 'med' => 'medium', 'low' => 'low', 'info' => 'info'];
$v        = DORKIFY_VERSION;
?><!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#080f17">
<meta name="robots" content="noindex, nofollow">
<title><?= $e(DORKIFY_TITLE) ?></title>
<meta name="description" content="<?= $e(DORKIFY_NAME . ' - ' . DORKIFY_TAGLINE) ?>">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="preload" href="fonts/JetBrainsMonoNerdFontMono-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/fonts.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/dorkify.css?v=<?= $v ?>">
</head>
<body>
<div class="wrap">

<nav class="crumbs">
  <span class="brand">THUGS<b>red</b> <span class="prod">Dorkify</span> <small>v<?= $e($v) ?></small></span>
  <span class="links"><a href="<?= $e(DORKIFY_WEBSITE) ?>" target="_blank" rel="noopener noreferrer">thugs.red</a><a href="<?= $e(DORKIFY_REPO_URL) ?>" target="_blank" rel="noopener noreferrer"><i>&#xf09b;</i> GitHub</a></span>
</nav>

<header class="hero">
  <img class="mascot" src="assets/mascot.svg?v=<?= $v ?>" width="260" height="240"
       alt="Dorkify mascot: a nerd in a hoodie and glasses, typing dorks on a laptop">
  <div class="hero-text">
    <div class="tag">THUGSred &middot; Google dork toolkit</div>
    <h1>Dork<span class="ify">ify</span></h1>
    <p class="tagline"><?= $e(DORKIFY_TAGLINE) ?></p>
    <div class="typer" aria-hidden="true"><i>&#xf002;</i><span id="typer"></span><span class="caret"></span></div>
    <p class="sub">Find what search engines have indexed that shouldn't be public, fix it at the source, then get it delisted.
      <?= $count ?> dorks, a query builder and the operator cheat sheet in one place.</p>
    <ol class="flow">
      <li><b>1</b> Set your scope below</li>
      <li><b>2</b> Run the dorks / build your own</li>
      <li><b>3</b> <a href="#remove">Fix &amp; remove</a></li>
    </ol>
  </div>
</header>

<div class="layout">

<nav class="toc" id="toc" aria-label="Contents">
  <div class="toc-group">
    <h4>Tools</h4>
    <a href="#builder" data-toc="builder"><i>&#xf1de;</i><span>Query builder</span></a>
    <a href="#operators" data-toc="operators"><i>&#xf02d;</i><span>Operators &amp; engines</span></a>
    <a href="#combine" data-toc="combine"><i>&#xf0eb;</i><span>Combining</span></a>
    <a href="#beyond" data-toc="beyond"><i>&#xf0e8;</i><span>Beyond Google</span></a>
    <a href="#remove" data-toc="remove" class="toc-fix"><i>&#xf0ad;</i><span>Fix &amp; remove</span></a>
  </div>
  <div class="toc-group">
    <h4>Dork library</h4>
    <?php foreach ($lib as $cat): ?>
      <a href="#<?= $e($cat['id']) ?>" data-toc="<?= $e($cat['id']) ?>" data-chip="<?= $e($cat['id']) ?>" title="<?= $e($cat['title']) ?>"><i><?= $glyph($cat['icon']) ?></i><span><?= $e($cat['short']) ?></span><small><?= count($cat['dorks']) ?></small></a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="main">
<section class="scope" id="scope" aria-label="Scope">
  <div class="scope-fields">
    <label class="fld"><span><i>&#xf05b;</i> domain</span>
      <input id="d" type="text" inputmode="url" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="example.dk"></label>
    <label class="fld"><span><i>&#xf0c0;</i> organisation</span>
      <input id="o" type="text" autocomplete="off" spellcheck="false" placeholder="Example A/S"></label>
    <label class="fld"><span><i>&#xf007;</i> person <small>(optional)</small></span>
      <input id="n" type="text" autocomplete="off" spellcheck="false" placeholder="Jane Doe"></label>
    <label class="fld"><span><i>&#xf1fa;</i> username <small>(optional)</small></span>
      <input id="u" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="jdoe"></label>
    <label class="fld grow"><span><i>&#xf0b0;</i> filter</span>
      <input id="f" type="search" autocomplete="off" spellcheck="false" placeholder="jenkins, .env, cpr, backup…"></label>
  </div>
  <div class="scope-row">
    <div class="sevs" role="group" aria-label="Severity filter">
      <?php foreach ($SEV as $k => $label): ?>
        <button type="button" class="sev-toggle sev-<?= $k ?>" data-sev="<?= $k ?>" aria-pressed="true"><?= $label ?></button>
      <?php endforeach; ?>
    </div>
    <div class="scope-meta">
      <span><b id="shown"><?= $count ?></b> / <?= $count ?> dorks</span>
      <button type="button" id="copy-all"><i>&#xf0c5;</i> copy shown</button>
      <button type="button" id="dl-all"><i>&#xf019;</i> .txt</button>
    </div>
  </div>
</section>

<!-- ============ builder ============ -->
<section class="panel" id="builder">
  <h2><i>&#xf1de;</i> Query builder</h2>
  <p class="lead">Fill in what you know - it assembles a correctly bracketed query. Comma-separate values to OR them together.
    Any dork's <b>edit</b> button loads it into the box below to adapt by hand.</p>

  <form class="bgrid" id="bform" autocomplete="off">
    <fieldset class="b-scope">
      <legend>scope</legend>
      <label><input type="radio" name="bscope" value="domain" checked> whole domain <code>site:d</code></label>
      <label><input type="radio" name="bscope" value="subs"> subdomains only <code>site:*.d -site:www.d</code></label>
      <label><input type="radio" name="bscope" value="else"> everywhere else <code>"d" -site:d</code></label>
      <label><input type="radio" name="bscope" value="none"> no scope</label>
    </fieldset>
    <label class="fld"><span>exact phrase <code>"…"</code></span><input data-b="phrase" type="text" spellcheck="false" placeholder="internal use only"></label>
    <label class="fld"><span>all of these words</span><input data-b="all" type="text" spellcheck="false" placeholder="password admin"></label>
    <label class="fld"><span>any of these <code>( OR )</code></span><input data-b="any" type="text" spellcheck="false" placeholder="backup, dump, export"></label>
    <label class="fld"><span>in title <code>intitle:</code></span><input data-b="intitle" type="text" spellcheck="false" placeholder="index of"></label>
    <label class="fld"><span>in URL <code>inurl:</code></span><input data-b="inurl" type="text" spellcheck="false" placeholder="admin, staging"></label>
    <label class="fld"><span>in text <code>intext:</code></span><input data-b="intext" type="text" spellcheck="false" placeholder="DB_PASSWORD"></label>
    <label class="fld"><span>exclude words <code>-</code></span><input data-b="not" type="text" spellcheck="false" placeholder="blog, news"></label>
    <label class="fld"><span>exclude sites <code>-site:</code></span><input data-b="notsite" type="text" spellcheck="false" placeholder="www.example.dk"></label>
    <label class="fld"><span>after <code>after:</code></span><input data-b="after" type="date"></label>
    <label class="fld"><span>before <code>before:</code></span><input data-b="before" type="date"></label>
    <fieldset class="b-types">
      <legend>file types <code>ext:</code></legend>
      <?php foreach (['pdf','doc','docx','xls','xlsx','csv','ppt','pptx','txt','sql','db','bak','old','zip','gz','log','env','json','xml','yml','conf','ini','config','pem','key'] as $t): ?>
        <label class="tchip"><input type="checkbox" value="<?= $t ?>"><span><?= $t ?></span></label>
      <?php endforeach; ?>
    </fieldset>
  </form>

  <div class="qbox">
    <label for="q" class="sr">Query</label>
    <textarea id="q" rows="3" spellcheck="false" autocapitalize="off" autocomplete="off" placeholder="Your query appears here - or type / paste one directly"></textarea>
    <div class="qhl" id="qhl" aria-hidden="true"></div>
    <div class="qbar">
      <span class="qmeta" id="qmeta">0 words</span>
      <span class="qacts">
        <button type="button" id="q-clear"><i>&#xf12d;</i> clear</button>
        <button type="button" id="q-copy"><i>&#xf0c5;</i> copy</button>
        <a class="btn" id="q-go" href="#" target="_blank" rel="noopener noreferrer"><i>&#xf002;</i> search Google</a>
      </span>
    </div>
    <div class="qengines">same query on:
      <a data-engine="bing" target="_blank" rel="noopener noreferrer">Bing</a>
      <a data-engine="ddg" target="_blank" rel="noopener noreferrer">DuckDuckGo</a>
      <a data-engine="brave" target="_blank" rel="noopener noreferrer">Brave</a>
      <a data-engine="yandex" target="_blank" rel="noopener noreferrer">Yandex</a>
      <span class="hint">- operator support differs, see the engine table below</span>
    </div>
  </div>
</section>

<!-- ============ operators ============ -->
<section class="panel" id="operators">
  <h2><i>&#xf02d;</i> Operator cheat sheet</h2>
  <div class="ops">
    <?php foreach ($OPS as [$op, $what, $ex, $st]): ?>
      <div class="op op-<?= $st ?>">
        <code class="op-name"><?= $e($op) ?></code>
        <span class="op-what"><?= $e($what) ?></span>
        <code class="op-ex hl"><?= $e($ex) ?></code>
        <span class="op-st"><?= ['ok' => 'works', 'flaky' => 'unreliable', 'dead' => 'gone'][$st] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <h3 class="sub-h">Same idea, other engines</h3>
  <p class="lead">Each engine has its own index - a file Google dropped may still be in Bing or Yandex. Run the high-severity dorks on more than one.</p>
  <div class="engines-wrap">
    <table class="engines">
      <thead><tr><th>goal</th><th>Google</th><th>Bing</th><th>DuckDuckGo</th><th>Yandex</th></tr></thead>
      <tbody>
        <?php foreach ($ENGINES as $row): ?>
          <tr><?php foreach ($row as $i => $cell): ?><?= $i === 0 ? '<th>' . $e($cell) . '</th>' : '<td><code>' . $e($cell) . '</code></td>' ?><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ============ combining ============ -->
<section class="panel" id="combine">
  <h2><i>&#xf0eb;</i> Combining operators</h2>
  <div class="patterns">
    <?php foreach ($PATTERNS as [$t, $why, $ex]): ?>
      <article class="pat">
        <h3><?= $e($t) ?></h3>
        <p><?= $e($why) ?></p>
        <code class="hl"><?= $e($ex) ?></code>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ library ============ -->
<div id="library">
<?php foreach ($lib as $cat): ?>
  <section class="panel cat" id="<?= $e($cat['id']) ?>" data-cat="<?= $e($cat['id']) ?>" data-title="<?= $e($cat['title']) ?>">
    <h2><i><?= $glyph($cat['icon']) ?></i> <?= $e($cat['title']) ?> <small class="cat-count"><?= count($cat['dorks']) ?></small></h2>
    <p class="lead"><?= $e($cat['lead']) ?></p>
    <div class="dorks">
      <?php foreach ($cat['dorks'] as $d): ?>
        <article class="dork" data-sev="<?= $d['s'] ?>" data-q="<?= $e($d['q']) ?>"
                 data-text="<?= $e(mb_strtolower($d['t'] . ' ' . $d['q'] . ' ' . ($d['n'] ?? '') . ' ' . ($d['f'] ?? '') . ' ' . $cat['title'])) ?>">
          <span class="sev sev-<?= $d['s'] ?>"><?= $SEV[$d['s']] ?></span>
          <div class="dbody">
            <h3><?= $e($d['t']) ?></h3>
            <code class="q hl" title="click to copy"><?= $e($d['q']) ?></code>
            <?php if (!empty($d['n'])): ?><p class="note"><?= $e($d['n']) ?></p><?php endif; ?>
            <?php if (!empty($d['f'])): ?><p class="fix"><i>&#xf0ad;</i> <?= $e($d['f']) ?></p><?php endif; ?>
          </div>
          <div class="acts">
            <a class="btn go" href="#" target="_blank" rel="noopener noreferrer"><i>&#xf002;</i> Google</a>
            <button type="button" class="copy"><i>&#xf0c5;</i> copy</button>
            <button type="button" class="edit"><i>&#xf040;</i> edit</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
  <p class="empty" id="empty" hidden>No dorks match the filter.</p>
</div>

<!-- ============ beyond google ============ -->
<section class="panel" id="beyond">
  <h2><i>&#xf0e8;</i> Beyond Google</h2>
  <p class="lead">Sources that see things search engines don't. Links fill in from the scope bar. The Wayback ones matter for clean-up:
    a file you deleted can still be served from the archive.</p>
  <div class="tools-grid">
    <?php foreach ($BEYOND as [$title, $what, $url, $note]): ?>
      <a class="tool-link" data-href="<?= $e($url) ?>" target="_blank" rel="noopener noreferrer">
        <b><?= $e($title) ?></b>
        <span><?= $e($what) ?></span>
        <code class="tool-url"><?= $e($url) ?></code>
        <?php if ($note): ?><small><?= $e($note) ?></small><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ fix & remove ============ -->
<section class="panel" id="remove">
  <h2><i>&#xf0ad;</i> Fix &amp; remove</h2>
  <p class="lead">Delisting only hides a result. Anyone who already has the URL - or uses another engine or the Wayback Machine - still gets the file.
    Work in this order.</p>

  <ol class="steps">
    <li><h3>Confirm &amp; scope it</h3>
      <p>Open the hit (it's yours) and note what's exposed, since when, and who could have seen it. Search Console &rarr; <em>Pages</em> and
        <a href="https://web.archive.org/" target="_blank" rel="noopener noreferrer">web.archive.org</a> show how long it's been out.
        Personal data exposed? GDPR gives you 72 hours to report a breach to the supervisory authority (Datatilsynet in Denmark).</p></li>
    <li><h3>Fix it at the source</h3>
      <p>Delete or move the file, put the area behind authentication, or make the URL return <code>404</code>/<code>410</code>.
        <b>Rotate every leaked password, key and token first</b> - assume it has been copied.</p></li>
    <li><h3>Stop it being re-indexed</h3>
      <p>Use <code>noindex</code> - a <code>&lt;meta name="robots" content="noindex"&gt;</code> tag, or the <code>X-Robots-Tag: noindex</code> header for PDFs and other files.
        <b>Don't block it in robots.txt yet:</b> a Disallow doesn't remove an indexed URL, and it stops Google seeing your noindex.</p></li>
    <li><h3>Request removal</h3>
      <ul class="links">
        <li><a href="https://search.google.com/search-console/removals" target="_blank" rel="noopener noreferrer">Search Console &rarr; Removals</a> &mdash; your own verified site. Hides a URL or prefix for about 6 months; steps 2&ndash;3 make it permanent.</li>
        <li><a href="https://search.google.com/search-console/remove-outdated-content" target="_blank" rel="noopener noreferrer">Remove outdated content</a> &mdash; anyone, for pages that are already gone or changed but still show in results or snippets.</li>
        <li><a href="https://support.google.com/websearch/answer/9673730" target="_blank" rel="noopener noreferrer">Personal info / doxxing removal</a> &mdash; ID numbers, bank details, contact info, login credentials on sites you don't control.</li>
        <li><a href="https://support.google.com/legal/answer/3110420" target="_blank" rel="noopener noreferrer">Google legal removals</a> &mdash; EU privacy (right to be forgotten), copyright and other legal requests.</li>
        <li><a href="https://www.bing.com/webmasters/tools/contentremoval" target="_blank" rel="noopener noreferrer">Bing content removal</a> &mdash; Bing also feeds DuckDuckGo, Ecosia and others.</li>
        <li>Internet Archive &mdash; the site owner can email <code>info@archive.org</code> to have captures excluded.</li>
      </ul></li>
    <li><h3>Third-party hosted leaks</h3>
      <p>Report to the platform (paste site, cloud provider, Trello/Notion owner) or the repo owner. For GitHub, follow
        <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository" target="_blank" rel="noopener noreferrer">removing sensitive data</a>.
        Once it's gone, use <em>Remove outdated content</em>. Someone else's exposure? Tell them via their <code>/.well-known/security.txt</code>.</p></li>
    <li><h3>Re-audit</h3>
      <p>Rerun the high and critical dorks with <code class="hl">after:YYYY-MM-DD</code> set to the last audit date, and add a check to your release process.</p></li>
  </ol>

  <div class="snips">
    <figure>
      <figcaption>Apache &mdash; no listings, no dotfiles or leftovers, noindex a private folder</figcaption>
<pre><code>Options -Indexes
RedirectMatch 404 "/\.(?!well-known/)"
&lt;FilesMatch "\.(bak|old|orig|save|swp|sql|log|env|ini|conf)$"&gt;
    Require all denied
&lt;/FilesMatch&gt;
&lt;Directory "/var/www/site/internal"&gt;
    Header set X-Robots-Tag "noindex, nofollow"
&lt;/Directory&gt;</code></pre>
    </figure>
    <figure>
      <figcaption>nginx &mdash; the same</figcaption>
<pre><code>autoindex off;
location ~ /\.(?!well-known/) { return 404; }
location ~* \.(bak|old|orig|save|swp|sql|log|env|ini|conf)$ { return 404; }
location /internal/ {
    add_header X-Robots-Tag "noindex, nofollow" always;
}</code></pre>
    </figure>
  </div>

  <p class="small">More: <a href="https://developers.google.com/search/docs/crawling-indexing/block-indexing" target="_blank" rel="noopener noreferrer">Google: block indexing with noindex</a> &middot;
    <a href="https://www.exploit-db.com/google-hacking-database" target="_blank" rel="noopener noreferrer">Google Hacking Database (GHDB)</a> for thousands more patterns.</p>
</section>

</div><!-- /.main -->
</div><!-- /.layout -->

<footer>
  <p>Made by <a href="<?= $e(DORKIFY_WEBSITE) ?>" target="_blank" rel="noopener noreferrer">THUGSred</a>, the Danish hacking community.</p>
  <p><i>&#xf132;</i> Searching is passive, but opening a hit downloads it. Only handle data you're responsible for, and report other people's exposures to them.</p>
  <p><?= $e(DORKIFY_NAME) ?> v<?= $e($v) ?> &middot; <?= $e(DORKIFY_TAGLINE) ?> &middot; authorized use only &middot;
    <a href="<?= $e(DORKIFY_REPO_URL) ?>" target="_blank" rel="noopener noreferrer"><?= $e(DORKIFY_REPO) ?></a></p>
</footer>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="assets/dorkify.js?v=<?= $v ?>"></script>
</body>
</html>
