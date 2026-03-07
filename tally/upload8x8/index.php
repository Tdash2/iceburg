<?php
include __DIR__ . "/../../config.php";

session_start();

$videohuballowedd = $_SESSION['tally'];
if ($videohuballowedd == "false") {
    showPluginExpired("Sorry, The Tally plugin is not licensed.");
    exit;
}

if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 4)) { showAccessDenied(); exit; }
include __DIR__ . "/../../header.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ESP32 Web Flasher</title>
  <script
    type="module"
    src="https://unpkg.com/esp-web-tools@10/dist/web/install-button.js?module"
  ></script>
  <style>
    body { font-family: sans-serif; background: #232323; color: #eee; }
    #container { max-width: 600px; margin: auto; }
    #log { white-space: pre-wrap; background: #f0f0f0; padding: 1em; height: 250px; overflow-y: auto; margin-top: 1em; border: 1px solid #ccc; }
  </style>
</head>
<body>
  <div id="container">
    <h1>Install Iceburg Tally 8x8</h1>
    <h3>Click the connect button to program the client.</h3>
    <esp-web-install-button id="flashButton"></esp-web-install-button>

  </div>

  <script type="module">
    const button = document.getElementById("flashButton");
    const logDiv = document.getElementById("log");

    function log(msg) {
      logDiv.textContent += msg + "\n";
      logDiv.scrollTop = logDiv.scrollHeight;
    }

    // Generate a manifest pointing to the firmware on your server
    const manifest = {
      name: "Iceburg Tally Client 8x8",
      version: "1.0.0",
      builds: [
        {
          chipFamily: "ESP32-S3",
          parts: [
            { path: "https://<?php echo $serverurl= $_SERVER['HTTP_HOST']; ?>/tally/upload8x8/firmware.bin", offset: 0 }
          ]
        }
      ]
    };

    const blob = new Blob([JSON.stringify(manifest)], { type: "application/json" });
    button.manifest = URL.createObjectURL(blob);

    // Capture logs from ESP Web Tools
    button.addEventListener("log", (ev) => {
      log(ev.detail.message);
    });

    button.addEventListener("error", (ev) => {
      log("Error: " + ev.detail.message);
    });

    log("Ready to flash firmware from server.");
  </script>
</body>
</html>
