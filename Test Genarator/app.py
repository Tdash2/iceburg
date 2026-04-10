import subprocess
import threading
import time
import json
import os
import re
import logging
import warnings

logging.getLogger("werkzeug").setLevel(logging.ERROR)
logging.getLogger("flask").setLevel(logging.ERROR)

warnings.filterwarnings("ignore")

from flask import Flask, request, render_template_string, jsonify





# silence Flask dev server warning + logs



app = Flask(__name__)

STATE_FILE = "state.json"
TIME_FILE = "time.txt"
TEXT1_FILE = "text1.txt"
TEXT2_FILE = "text2.txt"
IP_FILE = "ip.txt"

FONT = "/usr/share/fonts/truetype/Roboto/Roboto_Condensed-ExtraBold.ttf"

FFMPEG_PROCESS = None


# ----------------------------
# FILE SAFE WRITE
# ----------------------------
def write_file(path, content):
    tmp = path + ".tmp"
    with open(tmp, "w") as f:
        f.write(content)
    os.replace(tmp, path)


# ----------------------------
# STATE
# ----------------------------
def load_state():
    if not os.path.exists(STATE_FILE):
        return {"muted": False, "text1": "", "text2": ""}
    try:
        with open(STATE_FILE, "r") as f:
            return json.load(f)
    except:
        return {"muted": False, "text1": "", "text2": ""}


def save_state(s):
    with open(STATE_FILE, "w") as f:
        json.dump(s, f, indent=2)


# ----------------------------
# GET DECKLINK DEVICES
# ----------------------------
def get_devices():
    try:
        r = subprocess.run(
            ["ffmpeg", "-f", "decklink", "-list_devices", "1", "-i", "dummy"],
            capture_output=True,
            text=True
        )
        return re.findall(r"'([^']+)'", r.stderr)
    except:
        return []


# ----------------------------
# CLOCK LOOP
# ----------------------------
def clock_loop():
    while True:
        now = time.time()
        ms = int((now % 1) * 1000)
        t = time.strftime("%H:%M:%S", time.localtime(now))
        write_file(TIME_FILE, f"{t}.{ms:03d}")
        time.sleep(0.01)
import socket

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except:
        return "unknown"
        
def ip_loop():
    last_ip = None

    while True:
        ip_addr = get_local_ip()
        formatted = "WEB UI: HTTP://" + ip_addr +":8100"

        if ip_addr != last_ip:
            write_file(IP_FILE, formatted)
            last_ip = ip_addr

        time.sleep(5)      


# ----------------------------
# TEXT LOOP
# ----------------------------
def text_loop():
    last = {}

    while True:
        state = load_state()

        if state.get("text1") != last.get("t1"):
            write_file(TEXT1_FILE, state.get("text1", ""))
            last["t1"] = state.get("text1")

        if state.get("text2") != last.get("t2"):
            write_file(TEXT2_FILE, state.get("text2", ""))
            last["t2"] = state.get("text2")

        time.sleep(0.2)


# ----------------------------
# START FFMPEG
# ----------------------------
def start_ffmpeg():
    global FFMPEG_PROCESS

    devices = get_devices()
    if not devices:
        print("❌ No DeckLink devices found")
        return

    device = devices[0]
    state = load_state()
    muted = state.get("muted", False)

    print(f"Using DeckLink device: {device} | muted={muted}")

    vf = (
        f"drawtext=fontfile={FONT}:textfile={IP_FILE}:reload=1:"
        f"fontsize=30:fontcolor=white:box=1:boxcolor=black@0.5:"
        f"x=10:y=10,"
        
        f"drawtext=fontfile={FONT}:textfile={TIME_FILE}:reload=1:"
        f"fontsize=330:fontcolor=white:box=1:boxcolor=black@0.5:"
        f"x=(w-text_w)/2:y=100,"

        f"drawtext=fontfile={FONT}:textfile={TEXT1_FILE}:reload=1:"
        f"fontsize=150:fontcolor=white:box=1:boxcolor=black@0.5:"
        f"x=(w-text_w)/2:y=450,"

        f"drawtext=fontfile={FONT}:textfile={TEXT2_FILE}:reload=1:"
        f"fontsize=100:fontcolor=white:box=1:boxcolor=black@0.5:"
        f"x=(w-text_w)/2:y=600,"

        f"format=uyvy422"
    )

    audio_input = (
        "aevalsrc=sin(2*PI*1000*t):sample_rate=48000:channel_layout=stereo"
        if not muted else
        "anullsrc=channel_layout=stereo:sample_rate=48000"
    )

    cmd = [
        "ffmpeg",
        "-re",
        "-f", "lavfi",
        "-i", "smptebars=size=1920x1080:rate=30000/1001",
        "-f", "lavfi",
        "-i", audio_input,
        "-vf", vf,
        "-pix_fmt", "uyvy422",
        "-c:v", "v210",
        "-c:a", "pcm_s16le",
        "-r", "30000/1001",
        "-map", "0:v:0",
        "-map", "1:a:0",
        "-f", "decklink",
        device
    ]

    if FFMPEG_PROCESS:
        try:
            FFMPEG_PROCESS.kill()
        except:
            pass

    FFMPEG_PROCESS = subprocess.Popen(cmd)


# ----------------------------
# WATCHDOG
# ----------------------------
def watchdog():
    while True:
        time.sleep(5)
        if FFMPEG_PROCESS and FFMPEG_PROCESS.poll() is not None:
            print("⚠️ FFmpeg crashed → restarting")
            start_ffmpeg()


# ----------------------------
# API
# ----------------------------
@app.route("/")
def index():
    return render_template_string(HTML)


@app.route("/state")
def state():
    return jsonify(load_state())


@app.route("/update", methods=["POST"])
def update():
    state = load_state()

    state["text1"] = request.form.get("text1", "")
    state["text2"] = request.form.get("text2", "")

    save_state(state)
    return ("", 204)


@app.route("/mute", methods=["POST"])
def mute():
    state = load_state()
    state["muted"] = not state.get("muted", False)

    save_state(state)
    start_ffmpeg()
    return ("", 204)


# ----------------------------
# UI
# ----------------------------
HTML = """


<h2>Test Pattern Genarator</h2>

Text 1:<br>
<input id="text1" size=32 maxlength="24"><br><br>

Text 2:<br>
<input id="text2" size=32 maxlength="38"><br><br>

<button id="muteBtn" onclick="toggleMute()">Mute / Unmute</button>
<span id="muteStatus" style="color:red; font-weight:bold;"></span>

<script>
let lastState = {};

function sendUpdate() {
    const data = new FormData();
    data.append("text1", text1.value);
    data.append("text2", text2.value);

    fetch("/update", { method: "POST", body: data });
}

function toggleMute() {
    fetch("/mute", { method: "POST" });
}

text1.oninput = text2.oninput = (() => {
    let t;
    return () => {
        clearTimeout(t);
        t = setTimeout(sendUpdate, 10);
    };
})();

// POLL SERVER
async function poll() {
    const res = await fetch("/state");
    const s = await res.json();

    if (JSON.stringify(s) !== JSON.stringify(lastState)) {
        lastState = s;

        text1.value = s.text1 || "";
        text2.value = s.text2 || "";

        if (s.muted) {
           // muteStatus.innerText = "MUTED";
            muteBtn.innerText = "Unmute";
        } else {
            //muteStatus.innerText = "";
            muteBtn.innerText = "Mute";
        }
    }
}

setInterval(poll, 5000);
poll();
</script>
"""


# ----------------------------
# MAIN
# ----------------------------
if __name__ == "__main__":
    write_file(TEXT1_FILE, "")
    write_file(TEXT2_FILE, "")

    threading.Thread(target=clock_loop, daemon=True).start()
    threading.Thread(target=text_loop, daemon=True).start()
    threading.Thread(target=watchdog, daemon=True).start()
    threading.Thread(target=ip_loop, daemon=True).start()
    start_ffmpeg()

    app.run(
    host="0.0.0.0",
    port=8100,
    debug=False,
    use_reloader=False
)