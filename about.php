<?php
include "config.php";
include "actvation/actvation.php";
session_start();

$result = checkKey($baselicense, "1.0.0", "20",$serverurl);

if ($result) {
    $owner = $result['owner'];
    $expires = $result['expires'];
    $level = (int)$result['level']; // ? force int
    $error = $result['error']; // ? force int
    

    if ($level === 1) {
 
    } elseif ($level === 2) {

    } elseif ($level === 3) {

    }
     else {
        showProgramExpired($error);
        exit;
    }
}

if (!validateUserSession($conn, 1)) { // 2 = required permission level
    showAccessDenied();
    exit;
}

?>
<?php
include "header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rundown Manager</title>
      <link rel="icon" type="image/x-icon" href="favicon.ico">

<style>  
 body {
             background-color: #333;
              }

a {
  display : block; /* or inline-block */
}
a iframe {
  pointer-events : none;
} 
#outer {

  display: flex;
  justify-content: center;
}
.table {
     width: 93%;
    max-width: 93%;
    color: #fff;
   
}
      .buttonBlue {
        display: inline-block;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        color: #ffffff;
        background-color: #0085ff;
        border-radius: 6px;
        outline: none;
      }
            .buttonRed {
        display: inline-block;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        color: #ffffff;
        background-color: #f51e0f;
        border-radius: 6px;
        outline: none;
      }
                  .buttonGreen {
        display: inline-block;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        color: #ffffff;
        background-color: #008000;
        border-radius: 6px;
        outline: none;
      }
      .table-hover.tbody.tr:hover {
    background-color: #1c2b3f;
}
/* Table row hover color */
.table-hover tbody tr:hover {
    background-color: #333 !important; /* keep your dark background */
    color: #ffffff !important;           /* ensure text stays white */
}

</style> 
</head>

<div style="padding-left:16px">
</div>


<b> <center style="padding: 15pt; color: #fff;"  >About Stats Manager</center> </b> <br>


<div id="outer">
<table class="table table-hover">
<thead>
<tr >
<th>Program Licensed To</th>
<th>Program Expires In</th>
<th>Support</th>
<th>Copyright</th>


  
</tr >
</thead>


   
<tr>




 <td> <div style= "min-height: 100px; display: flex; align-items: center; "> <?php echo $owner;?></a> </div> </td>
<td> <div style= "min-height: 100px; display: flex; align-items: center; "> <?php echo $expires;?></a> </div> </td>
<td>
  <div style="min-height: 100px; display: flex; align-items: center;">
    Need Help? Send us an email at&nbsp;<a href="mailto:<?php echo SUPPORT_EMAIL; ?>"><?php echo SUPPORT_EMAIL; ?></a>
  </div>
</td>


<td> <div style= "min-height: 100px; display: flex; align-items: center; "> &copy; 2025 Timothy Doescher All Rights Reserved </a> </div> </td>



</tr>
</thead>

</table>
</div>



</body>
</html>