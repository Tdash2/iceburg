#include <SPI.h>
#include <Ethernet.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include <Wire.h>
#include <Adafruit_NeoPixel.h>

// ================= LED =================
Adafruit_NeoPixel pixels(1, 38, NEO_GRB + NEO_KHZ800);

// ================= RELAY =================
#define SDA_PIN 42
#define SCL_PIN 41
#define TCA9554_ADDR 0x20

#define REG_OUTPUT 0x01
#define REG_CONFIG 0x03

uint8_t relayState = 0xFF;

// ================= ETHERNET =================
#define W5500_CS 16
#define SPI_SCK  15
#define SPI_MISO 14
#define SPI_MOSI 13

byte mac[6];

EthernetServer webServer(80);
EthernetServer rossServer(1000);
EthernetClient rossClient;

// ================= CONFIG =================
Preferences prefs;

char serverHost[64] = "192.168.127.20";
int clientID = 1;
const int serverPort = 80;

// ================= TALLY =================
#define MAX_INPUTS 128
#define TSL_PACKET 18

bool lastTally[MAX_INPUTS];
int inputCount = 0;

// ================= CONSOLE =================
#define CONSOLE_SIZE 4096
String consoleBuffer = "";

void logConsole(String msg)
{
  Serial.println(msg);

  consoleBuffer += msg + "\n";

  if(consoleBuffer.length() > CONSOLE_SIZE)
    consoleBuffer.remove(0, consoleBuffer.length() - CONSOLE_SIZE);
}

// ================= LED =================
void setPixel(uint8_t r,uint8_t g,uint8_t b)
{
  pixels.setPixelColor(0,pixels.Color(r,g,b));
  pixels.show();
}

// ================= RELAY =================
void writeTCA(uint8_t reg,uint8_t val)
{
  Wire.beginTransmission(TCA9554_ADDR);
  Wire.write(reg);
  Wire.write(val);
  Wire.endTransmission();
}

void setRelay(uint8_t ch, bool on)
{
  if (ch > 7) return;

  if (on)
    relayState |= (1 << ch);   // ON = 1
  else
    relayState &= ~(1 << ch);  // OFF = 0

  writeTCA(REG_OUTPUT, relayState);
}

// ================= CONFIG =================
void loadConfig()
{
  prefs.begin("tally",true);

  prefs.getString("host",serverHost,sizeof(serverHost));
  clientID = prefs.getInt("id",1);

  prefs.end();
}

void saveConfig()
{
  prefs.begin("tally",false);

  prefs.putString("host",serverHost);
  prefs.putInt("id",clientID);

  prefs.end();
}

// ================= WEB SERVER =================
void handleWeb()
{
  EthernetClient c = webServer.available();
  if(!c) return;

  String req = c.readStringUntil('\r');
  c.read();

  // ---------- Console raw ----------
  if(req.startsWith("GET /console"))
  {
    c.println("HTTP/1.1 200 OK");
    c.println("Content-Type:text/plain");
    c.println();

    c.print(consoleBuffer);

    c.stop();
    return;
  }

  // ---------- Debug page ----------
  if(req.startsWith("GET /debug"))
  {
    c.println("HTTP/1.1 200 OK");
    c.println("Content-Type:text/html\n");

    c.println("<html><body style='background:black;color:#0f0;font-family:monospace'>");
    c.println("<h3>ESP Ross Tally Console</h3>");
    c.println("<pre id='log'></pre>");

    c.println("<script>");
    c.println("async function update(){");
    c.println("let r=await fetch('/console');");
    c.println("let t=await r.text();");
    c.println("document.getElementById('log').textContent=t;");
    c.println("}");
    c.println("setInterval(update,1000);");
    c.println("update();");
    c.println("</script>");

    c.println("</body></html>");

    c.stop();
    return;
  }

  // ---------- Status ----------
  if(req.startsWith("GET /status"))
  {
    c.println("HTTP/1.1 200 OK\nContent-Type:text/html\n\n");

    c.println("Ross Tally System<br>");
    c.println("IP: "+String(Ethernet.localIP()));
    c.println("<br>Client ID: "+String(clientID));

    c.stop();
    return;
  }

  // ---------- Config page ----------
  c.println("HTTP/1.1 200 OK\nContent-Type:text/html\n\n");

  c.println("<html><body style='background:#222;color:#fff'>");

  c.println("<h2>Ross Tally Config</h2>");

  c.println("<form method='POST' action='/save'>");

  c.println("Server IP: <input name='host' value='"+String(serverHost)+"'><br>");
  c.println("Client ID: <input name='id' value='"+String(clientID)+"'><br>");

  c.println("<button>Save</button></form>");

  c.println("<br><a href='/debug'>Debug Console</a>");

  c.println("</body></html>");

  c.stop();
}

// ================= ROSS TALLY =================
void readRoss()
{
  if(!rossClient || !rossClient.connected())
  {
    rossClient = rossServer.available();

    if(rossClient)
    {
      logConsole("Ross connected");
      setPixel(25,0,0);
    }
    else
    {
      setPixel(0,25,0);
      return;
    }
  }

  while(rossClient.available() >= TSL_PACKET)
  {
    uint8_t header = rossClient.read();
    uint8_t ctrl   = rossClient.read();

    char text[17];

    for(int i=0;i<16;i++)
      text[i] = rossClient.read();

    text[16] = 0;

    int addr = header - 0x80;
    bool program = ctrl & 0x02;

    if(addr>=0 && addr<MAX_INPUTS)
    {
      lastTally[addr] = program;

      if(addr+1 > inputCount)
        inputCount = addr+1;
    }

    logConsole("Input "+String(addr)+" UMD:"+String(text)+" PGM Tally Status: "+String(program));

   
  }
}

// ================= SEND TALLY =================
void sendToServer()
{
  EthernetClient c;

  if(!c.connect(serverHost,serverPort))
  {
    logConsole("Server connection FAILED");
    setPixel(0,25,0);
    return;
  }

  String url="/tally/settallystatus.php?id="+String(clientID);

for(int i = 0; i < 25 && i < inputCount; i++)
{
  url += "&ch" + String(i) + "=" + String(lastTally[i] ? "1" : "0");
}

  

  c.print("GET "+url+" HTTP/1.0\r\n\r\n");

  while(c.connected())
    while(c.available())
      c.read();

  c.stop();
  setPixel(25,0,0);
}

// ================= POLL OUTPUTS =================
void pollServer()
{
  EthernetClient c;

  if(!c.connect(serverHost,serverPort))
  {
    logConsole("Output poll FAILED");
    setPixel(0,25,0);
    return;
  }

  c.print("GET /tally/gettallystatus.php?id="+String(clientID)+" HTTP/1.0\r\n\r\n");

  while(c.connected())
  {
    String line=c.readStringUntil('\n');
    if(line=="\r") break;
  }

  StaticJsonDocument<512> doc;

  if(deserializeJson(doc,c))
  {
    logConsole("JSON parse error");
    c.stop();
    return;
  }

  JsonObject outputs = doc["outputs"];

  for(int i=0;i<8;i++)
    setRelay(i, outputs[String(i+1)] | false);

  c.stop();
  setPixel(25,0,0);
}

// ================= SETUP =================
void setup()
{
  Serial.begin(115200);

  pixels.begin();
  pixels.show();

  Wire.begin(SDA_PIN,SCL_PIN);

  writeTCA(REG_CONFIG,0x00);
  writeTCA(REG_OUTPUT,relayState);

  loadConfig();

  uint64_t chip = ESP.getEfuseMac();

  mac[0]=0x02;
  mac[1]=chip>>40;
  mac[2]=chip>>32;
  mac[3]=chip>>24;
  mac[4]=chip>>16;
  mac[5]=chip>>8;

  SPI.begin(SPI_SCK,SPI_MISO,SPI_MOSI);

  Ethernet.init(W5500_CS);

  if(Ethernet.begin(mac)==0)
  {
    logConsole("DHCP failed");
    while(true);
  }

  logConsole("IP: "+Ethernet.localIP().toString());

  webServer.begin();
  rossServer.begin();

  logConsole("Ross Tally Server ready");

  setPixel(0,0,25);
}

// ================= LOOP =================
unsigned long lastPoll=0;

void loop()
{
  handleWeb();

  readRoss();

  if(millis()-lastPoll > 100)
  {
    lastPoll = millis();

    sendToServer();
    pollServer();
  }
}