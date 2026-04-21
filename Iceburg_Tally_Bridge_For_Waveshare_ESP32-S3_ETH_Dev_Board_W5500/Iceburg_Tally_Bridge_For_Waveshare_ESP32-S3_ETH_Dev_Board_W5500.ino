#include <SPI.h>
#include <Ethernet.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include <vector>

// ===== Hardware =====
#define W5500_CS 14
#define SPI_SCK 13
#define SPI_MISO 12
#define SPI_MOSI 11

// ===== Network Defaults =====
byte mac[] = {0x02,0xAA,0xBB,0xCC,0xDD,0x30};

IPAddress localIP(192,168,1,200);
IPAddress gateway(192,168,1,1);
IPAddress subnet(255,255,255,0);

bool useDHCP = true;

// ===== Bridge Servers =====
IPAddress serverIPA(192,168,1,100);
IPAddress serverIPB(192,168,1,101);

int clientIDA = 1;
int clientIDB = 1;

const int serverPort = 80;

EthernetClient client;
EthernetServer webServer(80);

Preferences prefs;

// ===== Bridge Data =====
std::vector<bool> channels;

// ===== Timing =====
unsigned long lastPoll = 0;
const unsigned long pollInterval = 100;


// ============================================================
// Config Storage
// ============================================================

void loadConfig() {
  prefs.begin("bridge", true);

  serverIPA.fromString(prefs.getString("srvA", serverIPA.toString()));
  serverIPB.fromString(prefs.getString("srvB", serverIPB.toString()));

  clientIDA = prefs.getInt("idA", clientIDA);
  clientIDB = prefs.getInt("idB", clientIDB);

  useDHCP = prefs.getBool("dhcp", true);

  localIP.fromString(prefs.getString("ip", localIP.toString()));
  gateway.fromString(prefs.getString("gw", gateway.toString()));
  subnet.fromString(prefs.getString("sn", subnet.toString()));

  prefs.end();
}

void saveConfig() {
  prefs.begin("bridge", false);

  prefs.putString("srvA", serverIPA.toString());
  prefs.putString("srvB", serverIPB.toString());
  prefs.putInt("idA", clientIDA);
  prefs.putInt("idB", clientIDB);

  prefs.putBool("dhcp", useDHCP);
  prefs.putString("ip", localIP.toString());
  prefs.putString("gw", gateway.toString());
  prefs.putString("sn", subnet.toString());

  prefs.end();
}


// ============================================================
// Network Bridge Logic
// ============================================================

bool fetchFromA() {
  if (!client.connect(serverIPA, serverPort))
    return false;

  client.print("GET /tally/gettallystatus.php?id=" +
               String(clientIDA) +
               "&IP=" + Ethernet.localIP().toString() +
               " HTTP/1.0\r\n\r\n");

  // Skip HTTP headers
  while (client.connected()) {
    String line = client.readStringUntil('\n');
    if (line == "\r") break;
  }

  DynamicJsonDocument doc(2048);

  if (deserializeJson(doc, client)) {
    client.stop();
    return false;
  }

  JsonObject outputs = doc["outputs"];

  // ===== FIX: determine max channel =====
  int maxCh = 0;
  for (JsonPair kv : outputs) {
    int ch = atoi(kv.key().c_str());
    if (ch > maxCh) maxCh = ch;
  }

  // Resize channel array
  channels.clear();
  channels.resize(maxCh, false);

  // ===== FIX: assign by key instead of order =====
  for (JsonPair kv : outputs) {
    int ch = atoi(kv.key().c_str());

    if (ch > 0 && ch <= maxCh) {
      channels[ch - 1] = kv.value() | false;
    }
  }

  client.stop();
  return true;
}

void sendToB() {
  String url =
    "/tally/settallystatus.php?id=" +
    String(clientIDB) +
    "&IP=" + Ethernet.localIP().toString();

  for (int i = 0; i < channels.size(); i++)
    url += "&ch" + String(i+1) + "=" + String(channels[i] ? 1 : 0);

  if (!client.connect(serverIPB, serverPort))
    return;

  client.print(String("GET ") + url + " HTTP/1.0\r\n\r\n");

  while (client.connected())
    while (client.available()) client.read();

  client.stop();
}


// ============================================================
// Web Interface
// ============================================================

String getVal(String body, String key) {
  int p = body.indexOf(key + "=");
  if (p < 0) return "";

  int e = body.indexOf('&', p);
  return body.substring(
    p + key.length() + 1,
    e < 0 ? body.length() : e
  );
}

void handleWeb() {
  EthernetClient webClient = webServer.available();
  if (!webClient) return;

  String req = webClient.readStringUntil('\r');
  webClient.read();

  // ===== Save Settings =====
  if (req.startsWith("POST /save")) {

    while (webClient.available() &&
           webClient.readStringUntil('\n') != "\r");

    String body = webClient.readString();

    useDHCP = getVal(body,"dhcp") == "1";

    localIP.fromString(getVal(body,"ip"));
    gateway.fromString(getVal(body,"gw"));
    subnet.fromString(getVal(body,"sn"));

    serverIPA.fromString(getVal(body,"srvA"));
    serverIPB.fromString(getVal(body,"srvB"));

    clientIDA = getVal(body,"idA").toInt();
    clientIDB = getVal(body,"idB").toInt();

    saveConfig();

  String newIP = useDHCP ?
      "http://" + Ethernet.localIP().toString() :
      "http://" + localIP.toString();

    webClient.println("HTTP/1.1 200 OK");
    webClient.println("Connection: close\r\n");
    webClient.println("<html>Saved. Rebooting...</html>");
    webClient.println("<meta http-equiv='refresh' content='5; url="
                      + newIP + ":80'>");

    webClient.stop();
    delay(500);
    ESP.restart();
    return;
  }

  // ===== Status Page =====
  if (req.startsWith("GET /status")) {
    webClient.println("HTTP/1.1 200 OK");
    webClient.println("Content-Type:text/html");
    webClient.println("Connection: close\r\n");

    webClient.println("Iceburg Tally Bridge<br>");
    webClient.println("Bridging Server "+ serverIPA.toString()+" To Server "+  serverIPB.toString()+"<br>");
    webClient.println("Bridging IP: " + Ethernet.localIP().toString() + "<br>");
    webClient.println("DHCP: " + String(useDHCP ? "Yes":"No") + "<br>");

    webClient.stop();
    return;
  }

  // ===== Config Page =====
  webClient.println("HTTP/1.1 200 OK");
  webClient.println("Content-Type:text/html");
  webClient.println("Connection: close\r\n");

  webClient.println("<title>Configure Iceburg Tally Bridge</title>");
  webClient.println("<link rel='stylesheet' href='http://" +
                    serverIPA.toString() +
                    "/depends/bootstrap.min.css'>");
  webClient.println("<script src='http://" +
                    serverIPA.toString() +
                    "/depends/jquery.min.js'></script>");
  webClient.println("<script src='http://" +
                    serverIPA.toString() +
                    "/depends/bootstrap.min.js'></script>");
  webClient.println("<style>body { background-color: #232323; color: #FFF; }</style>");
  webClient.println("</head>");

  webClient.println("<body>");
  webClient.println("<div class='container'>");
  webClient.println("<div class='py-5 text-center'>");
  webClient.println("<h2>Configure Iceburg Tally Bridge </h2>");
  webClient.println("</div>");

  webClient.println("<form method='POST' action='/save'>");

  webClient.println("<h3>Network</h3>");

  webClient.println("<label>DHCP</label><br>");
  webClient.println("<select style='color:black' name='dhcp'>");
  webClient.println(
    String("<option value='1'") +
    (useDHCP?" selected>":" >") +
    "Yes</option>");

  webClient.println(
    String("<option value='0'") +
    (!useDHCP?" selected>":" >") +
    "No</option></select><br><br>");
 webClient.println("<div class='form-group'>");
  webClient.println("<label>IP</label><br>");
  webClient.println("<input type='text' name='ip' class='form-control' value='" +
                   localIP.toString() + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Gateway</label><br>");
  webClient.println("<input name='gw' class='form-control' value='" +
                   gateway.toString() + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Subnet</label><br>");
  webClient.println("<input name='sn' class='form-control' value='" +
                   subnet.toString() + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<h3>Servers</h3>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Iceburg Tally Server Sorce IP</label><br>");
  webClient.println("<input name='srvA' class='form-control' value='" +
                   serverIPA.toString() + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Iceburg Tally Server Sorce ID</label><br>");
  webClient.println("<input name='idA'  class='form-control' value='" +
                   String(clientIDA) + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Iceburg Tally Server Destnation IP</label><br>");
  webClient.println("<input name='srvB'  class='form-control' value='" +
                   serverIPB.toString() + "'>");
 webClient.println("</div>");


 webClient.println("<div class='form-group'>");
  webClient.println("<label>Iceburg Tally Server Destnation ID</label><br>");
  webClient.println("<input name='idB' class='form-control' value='" +
                   String(clientIDB) + "'><br>");

 webClient.println("</div>");

 webClient.println("<div class='form-group'>");

  webClient.println("<button class='btn btn-primary'>Save</button>");
  webClient.println("</form>");
  webClient.println("</body></html>");
  webClient.println(" <a class='btn btn-primary' href='http://" +
                     serverIPA.toString() +
                    "'>Sorce Iceburg Server Login</a>");
    webClient.println(" <a class='btn btn-primary' href='http://" +
                     serverIPB.toString() +
                    "'>Destnation Iceburg Server login</a>");                  

  webClient.stop();
}


// ============================================================
// Setup / Loop
// ============================================================

void setup() {
  Serial.begin(115200);
  delay(1000);

  loadConfig();

  SPI.begin(SPI_SCK, SPI_MISO, SPI_MOSI);
  Ethernet.init(W5500_CS);

  if (useDHCP) {
    if (!Ethernet.begin(mac)) {
      Serial.println("DHCP failed");
      while(true) delay(1000);
    }
  } else {
    Ethernet.begin(mac, localIP, gateway, gateway, subnet);
  }

  webServer.begin();

  Serial.println(Ethernet.localIP());
}

void loop() {
  handleWeb();

  if (millis() - lastPoll >= pollInterval) {
    lastPoll = millis();

    if (fetchFromA())
      sendToB();
  }

  delay(5);
}