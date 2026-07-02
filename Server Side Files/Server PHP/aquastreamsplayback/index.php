<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
  showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, $_GET['id'])) {
    showAccessDenied();
    exit;
}
$idd= $_GET['id'];
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 14 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGET,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();




// ----------------------
// PROXY EVERYTHING EXCEPT "/"
// ----------------------
if (isset($_GET['proxy'])) {

    $path = $_GET['proxy'];

    $url = $TARGET . $path;

    // preserve extra query params except proxy
    $query = $_GET;
    unset($query['proxy']);

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }

    $response = curl_exec($ch);

    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($contentType) {
        header("Content-Type: $contentType");
    }

    http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    echo $response;

    curl_close($ch);
    exit;
}else{

?>





<!DOCTYPE html>
<html>
<?php
include "../header.php";
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqua Streams Playback</title>

    <script>
        
            let currentButtons = [];

            /* ===================== SCALE SYSTEM ===================== */
            let BUTTON_SCALE = 0.67; // 1 = normal size

            function scale(value) {
      return value * BUTTON_SCALE;
}

            /* ======================= PLAY BUTTON ======================= */
            function sendRequest(button) {
      var string = button.getAttribute("String");
            var loop = button.getAttribute("Loop") === 'true';
            var url = "?proxy=/play/&id=<?php echo $idd;?>&file=" + encodeURIComponent(string) + "&loop=" + loop;
            fetch(url);
}

            function togglePause() {
                fetch('?proxy=/pause/&id=<?php echo $idd;?>');
}

            /* ======================= BUTTON RENDER ======================= */
            function createButtons(buttonsData) {
      const container = document.getElementById('buttonContainer');

            let existingButtons = Array.from(container.getElementsByTagName('button'))
        .filter(b => b.id !== 'playPauseBtn');

      buttonsData.forEach((buttonData) => {
        const existingButton = existingButtons.find(
          btn => btn.getAttribute('String') === buttonData.stringg
            );

            if (existingButton) {
                existingButton.style.display = 'block';
            animateUpdate(existingButton, buttonData);
          existingButtons = existingButtons.filter(btn => btn !== existingButton);
        } else {
                animateAdd(buttonData);
        }
      });

      existingButtons.forEach(button => animateRemove(button));
}

            /* ======================= SCALE APPLY ======================= */
            function applyScale(button, data) {
      const baseWidth = 280;
            const baseHeight = 90;

            button.style.position = 'absolute';

            button.style.width = scale(baseWidth) + 'px';
            button.style.height = scale(baseHeight) + 'px';

            button.style.left = scale(data.left) + 'px';
            button.style.top = scale(data.top) + 'px';

            button.style.fontSize = scale(20) + 'px';
}

            /* ======================= ANIMATIONS ======================= */
            function animateAdd(buttonData) {
      const container = document.getElementById('buttonContainer');
            const button = document.createElement('button');

            button.style.color = 'black';
            button.style.backgroundColor = buttonData.backgroundColor;
            button.style.borderColor = 'white';

            button.setAttribute('String', buttonData.filepath);
            button.setAttribute('Loop', buttonData.loop);

            button.innerHTML = buttonData.label + (buttonData.loop ? '<br /><i class="fa-solid fa-repeat"></i>' : '');
            button.onclick = function () {sendRequest(button); };

            applyScale(button, buttonData);

            button.style.opacity = 0;

            container.appendChild(button);

      setTimeout(() => {
                button.style.transition = 'opacity 0.5s';
            button.style.opacity = 1;
      }, 10);
}

            function animateRemove(button) {
                button.style.transition = 'opacity 0.5s';
            button.style.opacity = 0;
      setTimeout(() => button.remove(), 500);
}

            function animateUpdate(button, buttonData) {
                button.style.transition = 'all 0.5s ease-in-out';

            applyScale(button, buttonData);

            button.innerHTML = buttonData.label + (buttonData.loop ? "<br/> test": '');
            button.style.backgroundColor = buttonData.backgroundColor;
            button.setAttribute('Loop', buttonData.loop);
}

            /* ======================= STATUS BAR ======================= */
        function updateStatusBar(data) {
            const nowPlaying = document.getElementById('nowPlaying');
            const playPauseBtn = document.getElementById('playPauseBtn');
            const timeRemaining = document.getElementById('timeRemaining');
            const volumeSlider = document.getElementById('volumeSlider');

            nowPlaying.innerText = 'Now Playing: ' + (data.File_Name || '');
            playPauseBtn.innerText = data.Playbutton_Icon;
            playPauseBtn.onclick = togglePause;

            if (data.Time_left) {
                timeRemaining.innerText = data.Time_left.replace(/\r?\n/g, ' ');
            }

            // ? set slider from status (only if it exists)
            if (data.volume !== undefined && volumeSlider) {
                volumeSlider.value = data.volume;
            }
        }

        function setVolume(vol) {
            fetch('?proxy=/setvol/&id=<?php echo $idd;?>&vol=' + encodeURIComponent(vol));
        }
            function fetchStatus() {
                fetch('?proxy=/getstatus/&id=<?php echo $idd;?>')
                    .then(r => r.json())
                    .then(updateStatusBar)
                    .catch(() => { });
}

            /* ======================= BUTTON FETCH ======================= */
            function showConnectionError() {
                let errorDiv = document.getElementById('error-message');

            if (!errorDiv) {
                errorDiv = document.createElement('div');
            errorDiv.id = 'error-message';
            errorDiv.style.position = 'fixed';
            errorDiv.style.bottom = '10px';
            errorDiv.style.left = '10px';
            errorDiv.style.padding = '10px';
            errorDiv.style.backgroundColor = 'red';
            errorDiv.style.color = 'white';
            errorDiv.style.fontFamily = 'sans-serif';
            errorDiv.innerText = 'Connection error: Unable to fetch button data.';
            document.body.appendChild(errorDiv);
      }
}

            function fetchAndUpdateButtons() {
                fetch('?proxy=/getbuttons/&id=<?php echo $idd;?>')
                    .then(response => response.json())
                    .then(data => {
                        let errorDiv = document.getElementById('error-message');
                        if (errorDiv) errorDiv.remove();

                        if (JSON.stringify(data) !== JSON.stringify(currentButtons)) {
                            currentButtons = data;
                            createButtons(data);
                        }
                    })
                    .catch(() => showConnectionError());
}

            /* ======================= INIT ======================= */
        window.onload = function () {
            fetchAndUpdateButtons();
            fetchStatus();

            const volumeSlider = document.getElementById('volumeSlider');
            volumeSlider.addEventListener('input', (e) => {
                setVolume(e.target.value);
            });

            setInterval(fetchAndUpdateButtons, 1000);
            setInterval(fetchStatus, 600);
        };

    </script>
    </script>
</head>

<body style="background-color: black; margin:0;padding-top: 80px">

    <!-- STATUS BAR -->
    <div style="position:fixed; top:70px; left:0; right:0; height:50px; background:#222; color:white; display:flex; align-items:center; padding:0 10px; font-family:sans-serif; z-index:1000; gap:10px;">

        <div id="nowPlaying" style="flex:1;">Now Playing: </div>

        <div id="timeRemaining"></div>

        <!-- ?? VOLUME SLIDER -->
        <div style="display:flex; align-items:center; gap:6px;">
            <i class="fa fa-volume-up" aria-hidden="true"></i>
            <input id="volumeSlider"
                   type="range"
                   min="0"
                   max="1"
                   step="0.001"
                   style="width:120px;">
        </div>

        <button id="playPauseBtn"
                style="height:30px; color: black; width:40px; display:flex; justify-content:center; align-items:center; line-height:0;">
        </button>
    </div>

    <!-- BUTTON CONTAINER OFFSET 60px -->
    <div id="buttonContainer" style="position:relative; top:60px;"></div>

</body>
</html>

<?php
}
?>
