<?php
// Start session
if (!isset($_SESSION)) session_start();

// Fetch user permissions
$userPerm = 0;

$allowedPluginsJson = "";

if (isset($_SESSION['loggedin'], $_SESSION['user_id']) && $_SESSION['loggedin'] === true) {
    $stmt = $conn->prepare("SELECT UserPermissions, allowedPlugins FROM `Admin Users` WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($userPermFromDb, $allowedPluginsJsonn);
    if ($stmt->fetch()) {
        $userPerm = $userPermFromDb;
        $allowedPluginsJson = $allowedPluginsJsonn;
        $_SESSION['user_permissions'] = $userPerm;
    }
    $stmt->close();
}

// Check plugin permission
function checkperm($id){
  if ($_SESSION['user_permissions'] >= 3){
  return true;
  }else{

    global $allowedPluginsJson;
    $allowedPlugins = json_decode($allowedPluginsJson, true);
    if (!is_array($allowedPlugins)) return true; // fallback
    return in_array($id, $allowedPlugins);
    }
}

// Query devices/plugins
$query = "SELECT devices.*, deviceplugin.pluginName 
          FROM devices 
          LEFT JOIN deviceplugin ON deviceplugin.id = devices.pluginID";
           $currentUrl = $_SERVER['REQUEST_URI'];
    $redirectUrl = "index.php?redirect=" . urlencode($currentUrl);
    
    
    
    
    
    
// Prepare query
$stmt = $conn->prepare("
    SELECT id, panleName, deviceID, allowedusers
    FROM routerpanle
");

$stmt->execute();
$resultPanels = $stmt->get_result();

// Always initialize FIRST (prevents crashes)
$allPanels = [];
$panelsByDevice = [];

$currentUserID = $_SESSION['Userid'] ?? null;
$userperms = $_SESSION['user_permissions'] ?? 0;

// Only process if query actually returned rows
if ($resultPanels && $resultPanels->num_rows > 0) {

    while ($row = $resultPanels->fetch_assoc()) {

        // Safely decode JSON (prevents null errors)
        $allowedUsers = json_decode($row['allowedusers'] ?? '[]', true);

        if (!is_array($allowedUsers)) {
            $allowedUsers = [];
        }

        // Permission check
        if ($userperms < 3 && !empty($allowedUsers)) {
            if (!in_array($currentUserID, $allowedUsers)) {
                continue;
            }
        }

        // Save data safely
        $allPanels[] = $row;
        $panelsByDevice[$row['deviceID']][] = $row;
    }
}

$devices = [];

$stmttte = $conn->prepare("
    SELECT id, name, ip, pluginID, madisorce, lastping
    FROM devices
");
$stmttte->execute();

$resultdddwdd = $stmttte->get_result();

while ($row = $resultdddwdd->fetch_assoc()) {
    // Only include devices user is allowed to access
    if (checkperm($row['id'])) {
        $devices[] = $row;
    }
}



function userHasAnyPluginAccess(array $pluginIDs, array $devices): bool {
  
    foreach ($devices as $device) {
        if (in_array($device['pluginID'], $pluginIDs)) {
        if ($_SESSION['user_permissions'] >= 3){
  return true;
  }else{
            return true;
            }
        }
    }
    
    return false;
}

// IMPORTANT: always close
$stmt->close();


 
?>

<!-- Favicon -->
<link rel="shortcut icon" href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/favacon.ico" type="image/x-icon">
<link rel="icon" href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/favacon.ico" type="image/x-icon">


<!-- Bootstrap CSS / JS -->
<link rel="stylesheet" href="/depends/bootstrap.min.css">
<script src="/depends/jquery.min.js"></script>
<script src="/depends/bootstrap.min.js"></script>
<link rel="stylesheet" href="/depends/fontawesome/css/all.min.css">

<script src="/depends/dist/kioskboard-aio-2.3.0.min.js"></script>
<?php if(($_SESSION['UserEmail']  == "frontPanel")): ?>
<script>
KioskBoard.init({
  keysJsonUrl: "/depends/dist/kioskboard-keys.json",
  theme: "light"
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

  // Add required attributes to ALL inputs automatically
  document.querySelectorAll('input, textarea').forEach(el => {
    el.setAttribute("data-kioskboard-type", "keyboard");
    el.setAttribute("data-kioskboard-placement", "bottom");
  });

  // Now activate KioskBoard
  KioskBoard.run('input, textarea');

});
</script>
<?php endif; ?>
<style>
/* Header background and text */
#scanfcode {
  background: #181818;
  color: white;
  border-radius: 0;
  padding: 10px 20px;
  font-family: 'Encode Sans', sans-serif;
}

/* Logo */
.header-logo {
  display: block;
  margin: 0 auto;
  height: 30px;
  width: auto;
}

#logo {
  display: block;
  text-align: center;
  padding: 0;
  line-height: normal;
}

.logo-text {
  font-size: 19px;
  font-weight: bold;
  color: white;
  letter-spacing: 2px;
  margin-top: 5px;
}

/* Main nav links */
#link a {
  color: white;
  margin-left: 15px;
  letter-spacing: 1.2px;
  text-decoration: none;
}

#link a:hover {
  background-color: #373636;
}

/* Dropdown menus */
.dropdown-menu {
  background: #232323;
  border: none;
}

.dropdown-menu > li > a {
  color: white !important;
  padding: 8px 20px;
}

.dropdown-menu > li > a:hover {
  background: #2c2c2c;
  color: white !important;
}

/* Submenu container */
.dropdown-submenu {
  position: relative;
}

/* ? DEFAULT: open LEFT */
.dropdown-submenu > .dropdown-menu {
  display: none;
  position: absolute;
  top: 0;
  left: auto;
  right: 100%;
  margin-top: -1px;
  min-width: 180px;
  z-index: 1050;
}

/* ? If not enough room on left ? open RIGHT */
.dropdown-submenu.open-right > .dropdown-menu {
  left: 100%;
  right: auto;
}

/* Show submenu */
.dropdown-submenu:hover > .dropdown-menu,
.dropdown-submenu.open > .dropdown-menu {
  display: block;
}

/* Active/open states */
.nav .open > a,
.nav .open > a:focus,
.nav .open > a:hover {
  background-color: #373636;
}

/* Mobile toggle button */
#toggle-button .glyphicon {
  color: white;
}

/* Optional smoothness */
.dropdown-submenu > .dropdown-menu {
  transition: all 0.15s ease-in-out;
}
</style>

<script>

$('.dropdown-toggle').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var parent = $(this).parent();

    // Toggle manually
    $('.dropdown').not(parent).removeClass('open');
    parent.toggleClass('open');
});
$('.dropdown-menu').on('click', function (e) {
    e.stopPropagation();
});

function adjustSubmenuDirection(submenu) {
    var menu = submenu.children('.dropdown-menu');

    // Reset
    submenu.removeClass('open-right');

    // Temporarily show for measurement
    menu.css({ visibility: 'hidden', display: 'block' });

    var rect = menu[0].getBoundingClientRect();

    // Restore visibility
    menu.css({ visibility: '', display: '' });

    // If it overflows LEFT ? switch to RIGHT
    if (rect.left < 0) {
        submenu.addClass('open-right');
    }
}


$(function () {
    // Handle hover for submenu
$('.dropdown-submenu').hover(
    function () {
        var submenu = $(this);

        adjustSubmenuDirection(submenu); // ? ADD THIS

        submenu.children('.dropdown-menu').stop(true, true).slideDown(150);
    },
        function () {
            // Hide submenu when mouse leaves
            $(this).children('.dropdown-menu').stop(true, true).slideUp(150);
        }
    );

    // Handle click for submenu (for mobile)
 $('.dropdown-submenu > a').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var submenu = $(this).next('.dropdown-menu');

    // If already open ? do nothing (prevents closing on second tap)
    if (submenu.is(':visible')) {
        return;
    }

    // Close others
    $('.dropdown-submenu .dropdown-menu').not(submenu).slideUp(150);

    // Open this one ONLY
    submenu.slideDown(150);
});
    // Prevent dropdown from closing when clicking inside submenu
    $('.dropdown-submenu .dropdown-menu').on('click', function (e) {
        e.stopPropagation();
    });

    // Click outside closes all submenus
    $(document).on('click', function (e) {
        if ($(e.target).closest('.dropdown').length === 0) {
            $('.dropdown-submenu .dropdown-menu').slideUp(150);
        }
    });

    // Hide submenus when main dropdown closes
    $('.dropdown').on('hidden.bs.dropdown', function () {
        $('.dropdown-submenu .dropdown-menu').slideUp(150);
    });
});
</script>

<nav id="scanfcode" class="navbar" style="margin-bottom:0;">
  <div class="container-fluid">
<div class="navbar-header">
    <button type="button" id="toggle-button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
    <i class="fas fa-bars"></i>
</button>


    <!-- Logo with stacked text -->
    <a id="logo" class="navbar-brand" href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/index.php">
        <img src="/Iceburg Log.png" alt="Iceburg Logo" class="header-logo">
        <div class="logo-text">Iceburg</div>
    </a>
</div>


    <div class="collapse navbar-collapse" id="myNavbar">
      <ul id="link" class="nav navbar-nav navbar-right">

 <!-- Router Menu -->
<?php
$hasVirtualPanels = !empty($allPanels);


$result = $conn->query($query);
?>

<?php if ($userPerm >= 1 && ($hasVirtualPanels || userHasAnyPluginAccess([2,5,8], $devices))): ?>
<li class="dropdown" id="first-link">

  <a class="dropdown-toggle" data-toggle="dropdown" href="#">
    Router <span class="caret"></span>
  </a>

  <ul class="dropdown-menu">

    <!-- ===================== -->
    <!-- VIRTUAL PANELS (ALWAYS IF ASSIGNED) -->
    <!-- ===================== -->
    <?php if ($hasVirtualPanels): ?>
      <li class="dropdown-submenu">
        <a href="#">
          <i class="fa-solid fa-caret-left"></i> Virtual Panels
        </a>

        <ul class="dropdown-menu">
          <?php foreach ($allPanels as $panel): ?>
            <li>
              <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicvideohub/virtialrouterpanle.php?id=<?php echo $panel['id']; ?>">
                <?php echo htmlspecialchars($panel['panleName']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </li>
    <?php endif; ?>
    <!-- ===================== -->
    <!-- PLUGIN 2 ROUTER FEATURES -->
    <!-- ===================== -->
    <?php if ($result): ?>
      <?php while ($row = $result->fetch_assoc()):  ?>
        <?php if ($row['pluginID'] == 2 && checkperm($row['id'])): ?>
          <?php
            $id = $row['id'];
            $name = $row['name'] ?? 'NULL';
          ?>

          <li class="dropdown-submenu">
            <a href="#">
              <i class="fa-solid fa-caret-left"></i> <?php echo $name; ?>
            </a>

            <ul class="dropdown-menu">
              <li>
                <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicvideohub/bmdrouterroutes.php?id=<?php echo $id; ?>">
                  <?php echo $name; ?> Routes
                </a>
              </li>
              <li>
                <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicvideohub/bmdrouterinputnames.php?id=<?php echo $id; ?>">
                  <?php echo $name; ?> Input Names
                </a>
              </li>
              <li>
                <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicvideohub/bmdrouteroutputnames.php?id=<?php echo $id; ?>">
                  <?php echo $name; ?> Output Names
                </a>
              </li>
              <li>
                <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicvideohub/managevirtialpanles.php?id=<?php echo $id; ?>">
                  <?php echo $name; ?> Virtual Router Panel
                </a>
              </li>
            </ul>
          </li>

        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>


    <!-- ===================== -->
    <!-- PLUGIN 5 -->
    <!-- ===================== -->
    <?php if ($userPerm >= 1  && $result): ?>
     <?php $result = $conn->query($query);?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php if ($row['pluginID'] == 5 && checkperm($row['id'])): ?>
          <?php $id = $row['id']; $name = $row['name'] ?? 'NULL'; ?>

          <li>
            <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/blackmagicrouterpanel/index.php?id=<?php echo $id; ?>">
              <?php echo $name; ?> Button Mapping
            </a>
          </li>

        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>


    <!-- ===================== -->
    <!-- PLUGIN 8 -->
    <!-- ===================== -->
    <?php if ($userPerm >= 1 && checkperm("8") == "true" && $result): ?>
     <?php $result = $conn->query($query);?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php if ($row['pluginID'] == 8 && checkperm($row['id'])): ?>
          <?php $id = $row['id']; $name = $row['name'] ?? 'NULL'; ?>

          <li>
            <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/48blackmagicrouterpanel/index.php?id=<?php echo $id; ?>">
              <?php echo $name; ?> Button Mapping
            </a>
          </li>

        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>

  </ul>
</li>
<?php endif; ?>

        <!-- Audio Menu -->
        <?php if(($userPerm >= 1 ) && userHasAnyPluginAccess([1], $devices) ): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Audio <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 1 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li class="dropdown-submenu">
                    <a href="#"><i class="fa-solid fa-caret-left"></i></i> <?php echo $name;?></a>
                    <ul class="dropdown-menu">
                      
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/x32/inputs.php?id=<?php echo $id;?>"><?php echo $name;?> Input Config</a></li>
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/x32/mainmix.php?id=<?php echo $id;?>"><?php echo $name;?> Main Mix</a></li>
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/x32/bus.php?id=<?php echo $id;?>"><?php echo $name;?> Mix Buses</a></li>
                    </ul>
                  </li>
                <?php }}} ?>
          </ul> 
        </li>
        <?php endif; ?>

        <!-- Framesyncs Menu -->
        <?php if(($userPerm >= 1) && userHasAnyPluginAccess([3,10,11], $devices) ): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Framesyncs <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 3 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li class="dropdown-submenu">
                    <a href="#"><i class="fa-solid fa-caret-left"></i></i> <?php echo $name;?></a>
                    <ul class="dropdown-menu">
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/9905-mpx/?id=<?php echo $id;?>"><?php echo $name;?> Audio Embedding </a></li>
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/9905-mpx/inputselect.php?id=<?php echo $id;?>"><?php echo $name;?> Input Select</a></li>
                    </ul>
                  </li>
                <?php }}} ?>
                <?php
             if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 10 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li class="dropdown-submenu">
                    <a href="#"><i class="fa-solid fa-caret-left"></i></i> <?php echo $name;?></a>
                    <ul class="dropdown-menu">
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/ajafs2/?id=<?php echo $id;?>"><?php echo $name;?> Video Settings</a></li>
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/ajafs2/audio.php?id=<?php echo $id;?>"><?php echo $name;?> Audio Settings</a></li>
                    </ul>
                  </li>
                <?php }}} ?>  
                             <?php
             if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 11){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li class="dropdown-submenu">
                    <a href="#"><i class="fa-solid fa-caret-left"></i></i> <?php echo $name;?></a>
                    <ul class="dropdown-menu">
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/ajafs4/?id=<?php echo $id;?>"><?php echo $name;?> Video Settings</a></li>
                      <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/ajafs4/audio.php?id=<?php echo $id;?>"><?php echo $name;?> Audio Settings</a></li>
                    </ul>
                  </li>
                <?php }}} ?>  
          </ul> 
        </li>
        <?php endif; ?>
        
                <!-- Monitor Menu -->
        <?php if(($userPerm >= 1) && userHasAnyPluginAccess([6], $devices)): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Monitors <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 6 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/bmdSmartScope/?id=<?php echo  $id;?>"><?php echo  $name;?></a></li>
                  
                <?php }}} ?>
          </ul> 
        </li>
        <?php endif; ?>
        <!-- Encoders and cameras Menu -->
        <?php if(($userPerm >= 1)&& userHasAnyPluginAccess([9], $devices) ): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Cameras & Encoders <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 9 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/zowietekpov/?id=<?php echo  $id;?>"><?php echo  $name;?></a></li>
                  
                <?php }}} ?>
          </ul> 
        </li>
        <?php endif; ?>
        
        <?php if(($userPerm > 1) && userHasAnyPluginAccess([4,7], $devices)): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Tally<span class="caret"></span></a>
          
          
          <ul class="dropdown-menu">
          
          <?php if(($userPerm > 2)): ?>
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/tally/">Master Tally Grid</a></li>
            <?php if(($userPerm > 3)): ?>
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/tally/device.php">Setup Devices</a></li>
            <?php endif; ?>
             <?php endif; ?>
                  <li class="dropdown-submenu">
                    <a href="#"><i class="fa-solid fa-caret-left"></i></i>Device Tally Grid</a>
                    <ul class="dropdown-menu">
          <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 4 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/tally/devicetallygrid.php?id=<?php echo $id;?>"><?php echo $name;?> Tally Grid</a></li>
                <?php }}} ?>                               
                    </ul>                                        
                  </li>
        
            <?php
            if ($result = $conn->query($query)) {
              while ($row = $result->fetch_assoc()) {
                if ($row['pluginID'] == 7 && checkperm($row['id'])){
                  $id = $row['id']; 
                  $name = $row['name'] ?? 'NULL';
                  ?>
                  <li><a href="Https://<?php echo $_SERVER['HTTP_HOST'];?>/aitally/?id=<?php echo $id;?>">AI Tally <?php echo $name;?></a></li>
                <?php }}} ?>
                   </ul> 
                  </li>
        <?php endif; ?>

        <?php if($userPerm >= 3): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Equipment<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/device/">View Equipment</a></li>
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/device/equitment.php">Add new Equipment</a></li>
          </ul> 
        </li>
        <?php endif; ?>

        <?php if($userPerm >= 5): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#">Users<span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/addnewuser.php">Add A new User</a></li>
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/viewusers.php">View Users</a></li>
          </ul> 
        </li>
        <?php endif; ?>

        <!-- User Menu -->
        <?php if($userPerm >= 1 && (!($_SESSION['UserEmail']  == "frontPanel"))): ?>
        <li class="dropdown" id="first-link">
          <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $_SESSION['UserEmail']; ?><span class="caret"></span></a>
          <ul class="dropdown-menu">
          
         
            <li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/changepassword.php">Change Password</a></li>
           
             <!--<li><a href="Http://<?php echo $_SERVER['HTTP_HOST'];?>/about.php">About Iceburg</a></li>-->

            <li><a href='Http://<?php echo $_SERVER['HTTP_HOST'];?>/logout.php?url=<?php echo $redirectUrl; ?>'>Logout</a></li> 
          </ul> 
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
<script>
function confirmShutdown() {
    return confirm("This will immediately shut down the Iceburg server.\n\nAre you sure you want to continue?");
}
</script>