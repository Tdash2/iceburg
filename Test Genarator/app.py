import subprocess
import threading
import time
import json
import os
import socket
import io

from PIL import Image, ImageDraw, ImageFont
from flask import Flask, request, jsonify, render_template_string
import logging

log = logging.getLogger('werkzeug')
log.setLevel(logging.ERROR)

# =========================================================
# CONFIG
# =========================================================

WIDTH, HEIGHT = 1280, 720
FPS = 30

PREVIEW_FPS = 8
preview_interval = 1.0 / PREVIEW_FPS

STATE_FILE = "state.json"
FONT_PATH = "/usr/share/fonts/truetype/roboto/unhinted/RobotoCondensed-Bold.ttf"
DECKLINK_DEVICE = "DeckLink Duo (1)"

app = Flask(__name__)

# =========================================================
# GLOBALS (FIXED MEMORY MODEL)
# =========================================================

ffmpeg_process = None
state_lock = threading.Lock()

latest_frame_jpeg = None
latest_frame_lock = threading.Lock()

frame_lock = threading.Lock()

frame_a = Image.new("RGBA", (WIDTH, HEIGHT))
frame_b = Image.new("RGBA", (WIDTH, HEIGHT))
frame_write = frame_a
frame_read = None

# =========================================================
# STATE
# =========================================================

DEFAULT_STATE = {
    "text1": "Test Signal Generator",
    "text2": "Version 2.0",
    "muted": False,
    "box_x": 100,
    "box_y": 600,
    "box_w": 200,
    "box_h": 100,
    "box_speed_x": 10,
    "box_speed_y": 11,
    "output_mode": "1080i",
}

def load_state():
    if not os.path.exists(STATE_FILE):
        save_state(DEFAULT_STATE)
        return DEFAULT_STATE.copy()
    try:
        with open(STATE_FILE, "r") as f:
            return json.load(f)
    except:
        return DEFAULT_STATE.copy()

def save_state(s):
    with open(STATE_FILE, "w") as f:
        json.dump(s, f)

state = load_state()

# =========================================================
# NETWORK
# =========================================================

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except:
        return "unknown"

LOCAL_IP = get_local_ip()
IP_TEXT = f"WEB UI: http://{LOCAL_IP}:8100"

# =========================================================
# FONTS
# =========================================================

font_big = ImageFont.truetype(FONT_PATH, 150)
font_med = ImageFont.truetype(FONT_PATH, 80)
font_med_small = ImageFont.truetype(FONT_PATH, 55)
font_small = ImageFont.truetype(FONT_PATH, 40)

# =========================================================
# FFmpeg
# =========================================================

def get_video_mode():
    mode = state.get("output_mode", "1080i")

    if mode == "1080p":
        return {
            "w": 1920,
            "h": 1080,
            "fps": "60000/1001",
            "interlace": False
        }

    if mode == "720p":
        return {
            "w": 1280,
            "h": 720,
            "fps": "60000/1001",
            "interlace": False
        }

    # default = 1080i
    return {
        "w": 1920,
        "h": 1080,
        "fps": "30000/1001",
        "interlace": True
    }

# =========================================================
# FFmpeg (NOW PROPERLY MODE-AWARE)
# =========================================================

def start_ffmpeg():
    global ffmpeg_process

    video = get_video_mode()

    audio_input = (
        "aevalsrc=sin(2*PI*1000*t):sample_rate=48000:channel_layout=stereo"
        if not state["muted"]
        else "anullsrc=channel_layout=stereo:sample_rate=48000"
    )

    # overlay graph
    if video["interlace"]:
        vf = (
            f"[1:v]scale={video['w']}:{video['h']}:flags=fast_bilinear[ov];"
            f"[0:v][ov]overlay=0:0[v0];"
            f"[v0]tinterlace=mode=interleave_top[v]"
        )
    else:
        vf = (
            f"[1:v]scale={video['w']}:{video['h']}:flags=fast_bilinear[ov];"
            f"[0:v][ov]overlay=0:0[v]"
        )

    cmd = [
        "ffmpeg",
        "-hide_banner",
        "-loglevel", "quiet",
        "-nostats",

        "-f", "lavfi",
        "-i", f"nullsrc=size={video['w']}x{video['h']}:rate={video['fps']}",

        "-thread_queue_size", "512",
        "-f", "rawvideo",
        "-pix_fmt", "rgba",
        "-s", f"{WIDTH}x{HEIGHT}",
        "-r", str(FPS),
        "-i", "-",

        "-f", "lavfi",
        "-i", audio_input,

        "-filter_complex", vf,

        "-map", "[v]",
        "-map", "2:a",

        "-pix_fmt", "uyvy422",
        "-c:v", "v210",
        "-field_order", "tt" if video["interlace"] else "progressive",

        "-c:a", "pcm_s16le",
        "-r", video["fps"],

        "-f", "decklink",
        DECKLINK_DEVICE
    ]

    print("FFmpeg mode:", state["output_mode"])

    if ffmpeg_process:
        try:
            ffmpeg_process.kill()
            ffmpeg_process.wait(timeout=2)
        except:
            pass

    ffmpeg_process = subprocess.Popen(cmd, stdin=subprocess.PIPE, bufsize=0)

# =========================================================
# DRAW HELPERS
# =========================================================

def draw_text_box(draw, xy, text, font, padding=10):
    x, y = xy
    l, t, r, b = draw.textbbox((0, 0), text, font=font)
    w, h = r - l, b - t

    draw.rectangle((x - padding, y - padding, x + w + padding, y + h + padding),
                   fill=(0, 0, 0, 200))

    draw.text((x - l, y - t), text, font=font, fill=(255, 255, 255, 255))


def draw_centered_box(draw, center_y, text, font, width, padding=20):
    l, t, r, b = draw.textbbox((0, 0), text, font=font)
    tw, th = r - l, b - t

    bx1 = (width - tw) / 2
    by1 = center_y - th / 2

    draw.rectangle((bx1 - padding, by1 - padding,
                    bx1 + tw + padding, by1 + th + padding),
                   fill=(0, 0, 0, 255))

    draw.text((bx1 - l, by1 - t), text, font=font, fill=(255, 255, 255, 255))


def draw_smpte_bars(draw, w, h):
    colors = [
        (192, 192, 192),
        (192, 192, 0),
        (0, 192, 192),
        (0, 192, 0),
        (192, 0, 192),
        (192, 0, 0),
        (0, 0, 192),
    ]

    bar_w = w // len(colors)

    for i, c in enumerate(colors):
        draw.rectangle((i * bar_w, 0, (i + 1) * bar_w, h), fill=c)

    draw.rectangle((0, h, w, h), fill=(16, 16, 16))

# =========================================================
# GRAPHICS LOOP (FIXED - NO COPY PER FRAME)
# =========================================================

def graphics_loop():
    global frame_write, frame_read, ffmpeg_process, state

    frame_time = 1.0 / FPS
    next_frame = time.perf_counter()

    box = state

    while True:
        now = time.perf_counter()

        draw = ImageDraw.Draw(frame_write)

        draw.rectangle((0, 0, WIDTH, HEIGHT), fill=(0, 0, 0, 0))
        draw_smpte_bars(draw, WIDTH, HEIGHT)

        # motion
        box["box_x"] += box["box_speed_x"]

        if box["box_x"] <= 100 or box["box_x"] >= WIDTH - 300:
            box["box_speed_x"] *= -1
            
            

        draw.rectangle((box["box_x"], 470, box["box_x"] + box["box_w"], 670),
                       fill=(255, 255, 255, 255))

        draw_centered_box(draw, 200, time.strftime("%H:%M:%S"), font_big, WIDTH,10)
        
  
        draw_centered_box(draw, 324, box["text1"], font_med, WIDTH,3)
        draw_centered_box(draw, 400, box["text2"], font_med_small, WIDTH,3)

        draw_text_box(draw, (20, 20), IP_TEXT, font_small, 4)



        # SWAP BUFFER (NO COPY)
        with frame_lock:
            frame_read = frame_write
            frame_write = frame_b if frame_write is frame_a else frame_a

        if ffmpeg_process and ffmpeg_process.stdin:
            try:
                ffmpeg_process.stdin.write(frame_read.tobytes())
            except:
                pass

        next_frame += frame_time
        sleep = next_frame - time.perf_counter()

        if sleep > 0:
            time.sleep(sleep)
        else:
            next_frame = time.perf_counter()

# =========================================================
# PREVIEW WORKER (NO COPY)
# =========================================================

def preview_worker():
    global latest_frame_jpeg

    next_time = 0

    while True:
        now = time.perf_counter()

        if now < next_time:
            time.sleep(0.01)
            continue

        next_time = now + preview_interval

        with frame_lock:
            frame = frame_read

        if frame is None:
            continue

        frame = frame.resize((640, 360)).convert("RGB")

        buf = io.BytesIO()
        frame.save(buf, format="JPEG", quality=65)

        with latest_frame_lock:
            latest_frame_jpeg = buf.getvalue()

# =========================================================
# WATCHDOG
# =========================================================

def watchdog():
    global ffmpeg_process

    while True:
        time.sleep(5)
        if ffmpeg_process and ffmpeg_process.poll() is not None:
            start_ffmpeg()

# =========================================================
# FLASK API (RESTORED FULLY)
# =========================================================

@app.route("/")
def index():
    return render_template_string(HTML)

@app.route("/state")
def api_state():
    return jsonify(state)

@app.route("/update", methods=["POST"])
def update():
    with state_lock:
        state["text1"] = request.form.get("text1", "")
        state["text2"] = request.form.get("text2", "")
        save_state(state)
    return ("", 204)

@app.route("/preview.jpg")
def preview():
    with latest_frame_lock:
        if latest_frame_jpeg is None:
            return ("No frame", 404)

        return latest_frame_jpeg, 200, {
            "Content-Type": "image/jpeg",
            "Cache-Control": "no-store"
        }

@app.route("/mute", methods=["POST"])
def mute():
    with state_lock:
        state["muted"] = not state["muted"]
        save_state(state)
    start_ffmpeg()
    return ("", 204)

@app.route("/mode", methods=["POST"])
def set_mode():
    with state_lock:
        state["output_mode"] = request.form.get("mode", "1080i")
        save_state(state)
    start_ffmpeg()
    return ("", 204)

@app.route("/speed", methods=["POST"])
def speed():
    with state_lock:
        try:
            state["box_speed"] = int(request.form.get("speed"))
        except:
            pass
        save_state(state)
    return ("", 204)

# =========================================================
# WEB UI (UNCHANGED)
# =========================================================

HTML = """
<!DOCTYPE html>
<html>
<title>Test Signal Generator</title>
<link rel='stylesheet' href='http://"""+LOCAL_IP+"""/depends/bootstrap.min.css'>
<script src='http://"""+LOCAL_IP+"""/depends/jquery.min.js'></script>
<script src='http://"""+LOCAL_IP+"""/depends/bootstrap.min.js'></script>
<style>body { background-color:#232323; color:#FFF; }</style></head><body>

<body style="font-family:sans-serif;padding:40px;">


<div class='container'><div class='py-5 text-center'><h2>Test Signal Generator</h2></div>

<div class='form-group'><label>First Line Text</label>
<input class='form-control' id="text1" size="40"></div>

<div class='form-group'><label>Second Line Text</h3></label>
<input  class='form-control' id="text2" size="40"></div>

<div class='form-group'><label>Output Format</label>
<select class='form-control' id="mode" onchange="setMode()">
  <option value="1080p">1080p</option>
  <option value="1080i">1080i</option>
  <option value="720p">720p</option>
</select></div>

<div class='form-group'><label>Preview</label>
<img id="prev" class="form-control" src="/preview.jpg?t=1779764323151" width="640" style="
    height: 100%;
">
</div>
<script>
function setMode() {
    const fd = new FormData();
    fd.append("mode", mode.value);

    fetch("/mode", {
        method: "POST",
        body: fd
    });
}

async function refresh(){
    const r = await fetch("/state");
    const s = await r.json();

    // only update if user is NOT typing
    if (document.activeElement !== text1) text1.value = s.text1;
    if (document.activeElement !== text2) text2.value = s.text2;

    
    mode.value = s.output_mode;
}



function update(){
    const fd = new FormData();
    fd.append("text1", text1.value);
    fd.append("text2", text2.value);
    fetch("/update",{method:"POST",body:fd});
}

function updateSpeed(){
    const fd = new FormData();
    fd.append("speed", speed.value);
    fetch("/speed",{method:"POST",body:fd});
}

function toggleMute(){
    fetch("/mute",{method:"POST"});
}

text1.oninput = update;
text2.oninput = update;


setInterval(() => {
    document.getElementById("prev").src =
        "/preview.jpg?t=" + Date.now();
}, 300); // 5 fps preview


refresh();
setInterval(refresh, 1000);
</script>
</body>
</html>
"""

# =========================================================
# MAIN
# =========================================================

if __name__ == "__main__":
    start_ffmpeg()

    threading.Thread(target=graphics_loop, daemon=True).start()
    threading.Thread(target=preview_worker, daemon=True).start()
    threading.Thread(target=watchdog, daemon=True).start()

    app.run(host="0.0.0.0", port=8100, debug=False, use_reloader=False)