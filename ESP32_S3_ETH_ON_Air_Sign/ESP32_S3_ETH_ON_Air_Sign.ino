#include <SPI.h>
#include <Ethernet.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include "esp_system.h"


// ===== Board: Waveshare ESP32-S3-ETH =====
#define W5500_CS 14
#define SPI_SCK  13
#define SPI_MISO 12
#define SPI_MOSI 11

// ===== INPUT GPIOs (SET) =====


// ===== OUTPUT GPIOs (GET) =====
const int outputPins[1] = {3};

// ===== Network =====
byte mac[] = { 0x02, 0xAA, 0xBB, 0xCC, 0xDD, 0x30 };
EthernetClient client;
EthernetServer webServer(80);

// ===== Network Config =====
bool useDHCP = true;
IPAddress staticIP(192,168,1,50);
IPAddress gateway(192,168,1,1);
IPAddress subnet(255,255,255,0);
IPAddress dns(8,8,8,8);

// ===== Persistent Config =====
Preferences prefs;
char serverHost[64] = "iceburg";
int clientID = 19;        // SET = clientID, GET = clientID - 1
const int serverPort = 80;

// ===== Timing =====
unsigned long lastGetPoll = 0;
const unsigned long getInterval = 100;

// ===== Load / Save Config =====
void loadConfig() {
  prefs.begin("tally", true);

  prefs.getString("host", serverHost, sizeof(serverHost));
  clientID = prefs.getInt("id", clientID);

  useDHCP = prefs.getBool("dhcp", true);

  String ipStr = prefs.getString("ip", "192.168.1.50");
  if (ipStr.length()) staticIP.fromString(ipStr);

  ipStr = prefs.getString("gw", "192.168.1.1");
  if (ipStr.length()) gateway.fromString(ipStr);

  ipStr = prefs.getString("sn", "255.255.255.0");
  if (ipStr.length()) subnet.fromString(ipStr);

  ipStr = prefs.getString("dns", "8.8.8.8");
  if (ipStr.length()) dns.fromString(ipStr);

  prefs.end();
}

void saveConfig() {
  prefs.begin("tally", false);

  prefs.putString("host", serverHost);
  prefs.putInt("id", clientID);

  prefs.putBool("dhcp", useDHCP);
  prefs.putString("ip", staticIP.toString());
  prefs.putString("gw", gateway.toString());
  prefs.putString("sn", subnet.toString());
  prefs.putString("dns", dns.toString());

  prefs.end();
}

// ===== Web Interface =====
void handleWeb() {
  EthernetClient webClient = webServer.available();
  if (!webClient) return;

  String req = webClient.readStringUntil('\r');
  webClient.read(); // \n

  // ---- POST /save ----
  if (req.startsWith("POST /save")) {
    while (webClient.available() && webClient.readStringUntil('\n') != "\r");

    String body = webClient.readString();

    int h = body.indexOf("host=");
    int i = body.indexOf("id=");
    int d = body.indexOf("dhcp=");

    if (h >= 0) {
      String v = body.substring(h + 5, body.indexOf('&', h));
      v.replace("+", " ");
      v.toCharArray(serverHost, sizeof(serverHost));
    }

    if (i >= 0) {
      clientID = body.substring(i + 3, body.indexOf('&', i)).toInt();
    }

    if (d >= 0) {
      useDHCP = body.substring(d + 5, body.indexOf('&', d) < 0 ? body.length() : body.indexOf('&', d)).toInt();
    }

    auto getVal = [&](const char* key) -> String {
      int p = body.indexOf(String(key) + "=");
      if (p < 0) return "";
      int e = body.indexOf('&', p);
      return body.substring(p + strlen(key) + 1, e < 0 ? body.length() : e);
    };

    String v;
    v = getVal("ip");  if (v.length()) staticIP.fromString(v);
    v = getVal("gw");  if (v.length()) gateway.fromString(v);
    v = getVal("sn");  if (v.length()) subnet.fromString(v);
    v = getVal("dns"); if (v.length()) dns.fromString(v);



saveConfig();
delay(100);  // small delay to ensure data is written


String newIP = useDHCP ? "http://" + Ethernet.localIP().toString() : "http://" + staticIP.toString();

// Send a response to the client before restarting
webClient.println("HTTP/1.1 200 OK");
webClient.println("Content-Type: text/html");
webClient.println("Connection: close\r\n");
webClient.println("<html><body>");
webClient.println("<h3>Settings saved. Restarting device...</h3>");

webClient.println("<meta http-equiv='refresh' content='0; url=" + newIP + ":80'>"); // 5s delay
webClient.println("</body></html>");
webClient.stop();
    webClient.stop();
    ESP.restart(); // restart to apply new IP
  }

  // ---- GET /status ----
  if (req.startsWith("GET /status")) {
    webClient.println("HTTP/1.1 200 OK");
    webClient.println("Content-Type: text/html");
    webClient.println("Connection: close\r\n");

    webClient.println("Device Type: Iceburg Tally 8x8 <br>");
    webClient.println("Tally ID: " + String(clientID) + "<br>");
    webClient.println("IP: " + Ethernet.localIP().toString() + "<br>");
    webClient.println("Mode: " + String(useDHCP ? "DHCP" : "Static"));

    delay(1);
    webClient.stop();
    return;
  }

  // ---- HTML Form ----
  webClient.println("HTTP/1.1 200 OK");
  webClient.println("Content-Type: text/html");
  webClient.println("Connection: close\r\n");

  webClient.println("<html>");
  webClient.println("<head>");
  webClient.println("<title>Configure Iceburg Air Sign</title>");
  webClient.println("<link rel='stylesheet' href='http://" + String(serverHost) + "/depends/bootstrap.min.css'>");
  webClient.println("<script src='http://" + String(serverHost) + "/depends/jquery.min.js'></script>");
  webClient.println("<script src='http://" + String(serverHost) + "/depends/bootstrap.min.js'></script>");
  webClient.println("<style>body { background-color: #232323; color: #FFF; }</style>");
  webClient.println("</head>");
  webClient.println("<body>");
  webClient.println("<div class='container'>");
  webClient.println("<div class='py-5 text-center'>");
  webClient.println("<h2>Configure Iceburg Tally On Air Sign</h2>");
  webClient.println("<p>This device has 1 tally input, when it is triggered it will turn on the sign. </p>");
  webClient.println("</div>");

  webClient.println("<form method='POST' action='/save'>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>Server Host</label>");
  webClient.println("<input type='text' class='form-control' name='host' value='" + String(serverHost) + "' required>");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>Client ID</label>");
  webClient.println("<input type='text' class='form-control' name='id' value='" + String(clientID) + "' required>");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>IP Mode</label><br>");
  webClient.println("<input type='radio' name='dhcp' value='1' " + String(useDHCP ? "checked" : "") + "> DHCP ");
  webClient.println("<input type='radio' name='dhcp' value='0' " + String(!useDHCP ? "checked" : "") + "> Static");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>Static IP</label>");
  webClient.println("<input class='form-control' name='ip' value='" + staticIP.toString() + "'>");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>Gateway</label>");
  webClient.println("<input class='form-control' name='gw' value='" + gateway.toString() + "'>");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>Subnet</label>");
  webClient.println("<input class='form-control' name='sn' value='" + subnet.toString() + "'>");
  webClient.println("</div>");

  webClient.println("<div class='form-group'>");
  webClient.println("<label>DNS</label>");
  webClient.println("<input class='form-control' name='dns' value='" + dns.toString() + "'>");
  webClient.println("</div>");

  webClient.println("<button type='submit' class='btn btn-primary'>Save</button>");
  webClient.println(" <a class='btn btn-primary' href='http://" + String(serverHost) + "'>Iceburg Server Login</a>");
  webClient.println("</form>");
  webClient.println("</div>");
  webClient.println("</body>");
  webClient.println("</html>");

  delay(1);
  webClient.stop();
}

// ===== SET (Inputs → Server) =====


// ===== GET (Server → Outputs) =====
void pollOutputs() { 
  int getID = clientID;

  if (!client.connect(serverHost, serverPort)) return;

  client.print("GET /tally/gettallystatus.php?id=" + String(getID) +  "&IP=" + Ethernet.localIP().toString() + " HTTP/1.0\r\n\r\n");

 // Skip headers
  while (client.connected()) {
    String line = client.readStringUntil('\n');
    if (line == "\r") break;
  }

  StaticJsonDocument<512> doc;
  DeserializationError err = deserializeJson(doc, client);
  if (err) {
    Serial.println(err.c_str());
    client.stop();
    return;
  }

  JsonObject outputs = doc["outputs"];

  // Number of pins you actually use
  const int pinCount = sizeof(outputPins) / sizeof(outputPins[0]);

  for (int i = 0; i < pinCount; i++) {
    char key[4];
    itoa(i + 1, key, 10);  // "1", "2", "3", ...

    bool state = outputs[key] | false;
    digitalWrite(outputPins[i], state ? HIGH : LOW);
  }

  client.stop();
}
// ===== SETUP =====
void setup() {
  Serial.begin(115200);
  delay(1000);

  loadConfig(); // Load config before Ethernet

 

  for (int i = 0; i < 8; i++) {
    pinMode(outputPins[i], OUTPUT);
    digitalWrite(outputPins[i], LOW);
  }

  SPI.begin(SPI_SCK, SPI_MISO, SPI_MOSI);
  Ethernet.init(W5500_CS);

  bool ethOK;
  if (useDHCP) {
    ethOK = Ethernet.begin(mac);
  } else {
    Ethernet.begin(mac, staticIP, dns, gateway, subnet);
    ethOK = true;
  }

  if (!ethOK) {
    Serial.println("Ethernet failed");
    while (true) delay(1000);
  }

  webServer.begin();

  Serial.print("Web UI: http://");
  Serial.println(Ethernet.localIP());

}

// ===== LOOP =====
void loop() {
  handleWeb();
  if (millis() - lastGetPoll >= getInterval) {
    lastGetPoll = millis();
    pollOutputs();
  }
  delay(5);
}
