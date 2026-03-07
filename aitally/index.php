<?php
include "../config.php";
session_start();
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 4)) { showAccessDenied(); exit; }


include "../header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Voice HTTP Request Demo</title>
</head>
<style>
        /* General Reset */
        body {

            background-color: #232323;

        }

        /* Container */
        .container {

            width: 80%;


        }
        .h1, h1 {
    font-size: 36px;
    color: white;
}
#output {
    height: 67vh;          /* 80% of the viewport height */
    overflow-y: auto;      /* scroll when content exceeds height */
    background-color: #111;
    color: #fff;
    padding: 10px;
    border-radius: 6px;
    box-sizing: border-box;
    margin: 10px 0px;
}

.buttonGrean, .buttonRed {
    display: inline-block;
    padding: 5px 10px;
    text-align: center;
    text-decoration: none;
    color: #ffffff;
        background: green;
    border-radius: 6px;
    outline: none;
    max-width: fit-content;
}
        </style>
<body>
<div class="container">
  <h1>AI Tally</h1>
<button id="listenBtn" class="buttonGrean" onclick="toggleListening()">Start Recognition</button>

</br>
  <pre id="output"></pre>


</body>
</html>
</div>
<script>
const output = document.getElementById("output");
const listenBtn = document.getElementById("listenBtn");
let lastTranscript = "";
let recognition = null;
let isListening = false;

function log(message) {
  output.textContent += message + "\n";
}

function toggleListening() {
  if (!("webkitSpeechRecognition" in window)) {
    alert("Speech recognition not supported in this browser");
    return;
  }

  if (!recognition) {
    recognition = new webkitSpeechRecognition();
    recognition.lang = "en-US";
    recognition.interimResults = false;
    recognition.continuous = true;

    recognition.onstart = () => {
      log("Started Recognition");
      listenBtn.textContent = "Stop Recognition";
    };

    recognition.onend = () => {
      if (isListening) {
        recognition.start(); // auto-restart
      } else {
        log("Stoped Recognition");
        listenBtn.textContent = "Start Recognition";
      }
    };

    recognition.onresult = (event) => {
      const result = event.results[event.results.length - 1];
      const text = result[0].transcript.trim().toLowerCase();
      if (text === lastTranscript) return;
      lastTranscript = text;
      log("Text heard " + text);
      handleCommand(text);
    };

    recognition.onerror = (err) => {
      log("Error: " + err.error);
    };
  }

  // Toggle listening
  if (isListening) {
    isListening = false;
    recognition.stop();
  } else {
    isListening = true;
    recognition.start();
  }
}



// ---- INTENT PARSER ----
function handleCommand(text) {
  const command = text.toLowerCase();

  let request = null;

  if (command.includes("take one")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=1&ch2=0&ch3=0&ch4=0&ch5=0&ch6=0&ch7=0&ch8=0"
    };
  }
  if (command.includes("take two")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=1&ch3=0&ch4=0&ch5=0&ch6=0&ch7=0&ch8=0"
    };
  }
  if (command.includes("take three")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=1&ch4=0&ch5=0&ch6=0&ch7=0&ch8=0"
    };
  }
    if (command.includes("take four")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=0&ch4=1&ch5=0&ch6=0&ch7=0&ch8=0"
    };
  }
    if (command.includes("take five")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=0&ch4=0&ch5=1&ch6=0&ch7=0&ch8=0"
    };
  }
    if (command.includes("take six")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=0&ch4=0&ch5=0&ch6=1&ch7=0&ch8=0"
    };
  }
    if (command.includes("take seven")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=0&ch4=0&ch5=0&ch6=0&ch7=1&ch8=0"
    };
  }
    if (command.includes("take eight")) {
    request = {
      method: "GET",
      url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/tally/settallystatus.php?id=<?php echo $_GET['id'];?>&ch1=0&ch2=0&ch3=0&ch4=0&ch5=0&ch6=0&ch7=0&ch8=1"
    };
  }



  sendRequest(request);
}

// ---- SEND HTTP REQUEST ----
async function sendRequest(req) {
  log(`Sending Tally Change`);

  const response = await fetch(req.url, {
    method: req.method,
    headers: {
      "Content-Type": "application/json"
    },
    body: req.body ? JSON.stringify(req.body) : undefined
  });

  const data = await response.json();


}
</script>