
<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila'); // Set the timezone

include '../Includes/dbcon.php';
include '../Includes/session.php';



    $query = "SELECT tblclass.className,tblclassarms.classArmName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";
    $rs = $conn->query($query);
    $num = $rs->num_rows;
    $rrw = $rs->fetch_assoc();
    $statusMsg = "";

    if(isset($_POST['save'])){
    
      // Ensure $_POST['check'] exists
      if (!isset($_POST['check'])) {
          echo "<div class='alert alert-danger' style='margin-right:700px;'>No student was selected!</div>";
          exit;
      }
  
      // Get checked students
      $check = $_POST['check'];
      $dateTimeTaken = date("Y-m-d h:i A"); // Format: 2025-03-02 08:00 AM
      $dateTaken = date("Y-m-d", strtotime($dateTimeTaken)); 
      $timeTaken = date("H:i:s", strtotime($dateTimeTaken)); // Extract time only
      
      // Check if attendance is already taken
      $qurty = mysqli_query($conn, "SELECT * FROM tblattendance  
                            WHERE classId = '$_SESSION[classId]' 
                            AND classArmId = '$_SESSION[classArmId]' 
                            AND DATE(dateTimeTaken) = '$dateTaken' 
                            AND (status = '1' OR status = '3')");

      $count = mysqli_num_rows($qurty);
      $lateTime = "14:39:00"; // Define the threshold time for being late
      
      foreach ($check as $admissionNumber) {
          $status = (strtotime($timeTaken) > strtotime($lateTime)) ? 3 : 1;
      
          $sql = "INSERT INTO tblattendance (Lrn, classId, classArmId, dateTimeTaken, status) 
                  VALUES ('$admissionNumber', '$_SESSION[classId]', '$_SESSION[classArmId]', '$dateTimeTaken', '$status')
                  ON DUPLICATE KEY UPDATE status='$status'";
      
          $qquery = mysqli_query($conn, $sql);

          if (!$qquery) {
              echo "<div class='alert alert-danger' style='margin-right:700px;'>Error: " . mysqli_error($conn) . "</div>";
          }
      }
      $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;'>Attendance Taken Successfully!</div>";
      
  }

  if(isset($_POST['lock'])){
    
    $dateTaken = date("Y-m-d h:i A"); 
    $onlyDate = date("Y-m-d", strtotime($dateTaken)); 

    $classId = $_SESSION['classId'];
    $classArmId = $_SESSION['classArmId'];

    if (!$conn) {
        die("Database connection error: " . mysqli_connect_error());
    }

    $query = "SELECT Lrn FROM tblstudents 
              WHERE classId = '$classId' 
              AND classArmId = '$classArmId' 
              AND Lrn NOT IN (
                  SELECT Lrn FROM tblattendance 
                  WHERE classId = '$classId' 
                  AND classArmId = '$classArmId' 
                  AND DATE(dateTimeTaken) = '$onlyDate'
              )";


    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("<div class='alert alert-danger'>Error fetching students: " . mysqli_error($conn) . "</div>");
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $Lrn = $row['Lrn'];

        $sql = "INSERT INTO tblattendance (Lrn, classId, classArmId, dateTimeTaken, status) 
                VALUES ('$Lrn', '$classId', '$classArmId', '$dateTaken', '2')";

        $insertQuery = mysqli_query($conn, $sql);

        if (!$insertQuery) {
            echo "<div class='alert alert-danger'>Error inserting absent student: " . mysqli_error($conn) . "</div>";
        }
    }

    echo "<div class='alert alert-success'>Absent students recorded successfully!</div>";
    
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">



   <script>
    function classArmDropdown(str) {
    if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
        return;
    } else { 
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
        xmlhttp.send();
    }
  }

    


  function handleFastAPIResponse(response) {
    console.log("Response from FastAPI:", response);

    if (response.status === "success") {
        const Lrn = response.student_id; 

        const now = new Date();
        const timeTaken = now.toLocaleTimeString("en-GB", { timeZone: "Asia/Manila", hour12: false }).slice(0, 8);

        const lateTime = "14:39:00";
        const status = timeTaken > lateTime ? 3 : 1;

        fetch("recordAttendance.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `Lrn=${Lrn}&status=${status}` // Removed dateTimeTaken
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server Response:", data);
            showNotification("Attendance Recorded", `Student ID: ${Lrn} - Status: ${status == 1 ? "Present" : "Late"}`, "success");
            document.getElementById("statusMsg").innerHTML = `<div class='alert alert-success'>Attendance recorded successfully for Student ${Lrn}!</div>`;
        })
        .catch(error => {
            console.error("Error updating attendance:", error);
            document.getElementById("statusMsg").innerHTML = `<div class='alert alert-danger'>Error updating attendance.</div>`;
        });

    } else if (response.status === "failed") {
        document.getElementById("statusMsg").innerHTML = `<div class='alert alert-danger'>No Match Found!</div>`;
    } else {
        document.getElementById("statusMsg").innerHTML = `<div class='alert alert-danger'>An error occurred!</div>`;
    }

  }



    function showNotification(title, message, type) {
        const notification = document.createElement("div");
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `<strong>${title}:</strong> ${message}`;
        notification.style.position = "fixed";
        notification.style.top = "20px";
        notification.style.right = "20px";
        notification.style.zIndex = "9999";
        notification.style.padding = "10px";
        notification.style.borderRadius = "5px";
        notification.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    document.addEventListener("DOMContentLoaded", function () {
    const form = document.forms["attendanceForm"];

    if (!form) {
        console.error("Form not found!");
        return; 
    }

        form.addEventListener("submit", function (event) {
            const clickedButton = event.submitter; 

            console.log("Clicked button:", clickedButton ? clickedButton.name : "None"); 
            if (clickedButton && (clickedButton.name === "photo" || clickedButton.name === "qr")) {
            event.preventDefault(); // Prevent default only for these buttons
            
            if (clickedButton.name === "photo") {
                callFastAPI("/detect-face");
            } else if (clickedButton.name === "qr") {
                callFastAPI("/scan-qr");
            }
        }
        });
    });

    async function callFastAPI(endpoint) {
      const apiUrl = "http://127.0.0.1:8000" + endpoint; 

      try {
          const response = await fetch(apiUrl, {
              method: "GET",
              headers: {
                  "Content-Type": "application/json",
              },
          });

          const result = await response.json();
          console.log(result);
          handleFastAPIResponse(result); 
      } catch (error) {
          console.error("Error calling FastAPI:", error);
          showNotification("Server Error", "Could not connect to the FastAPI server.", "danger");
      }
  }
</script>
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Take Attendance (Today's Date : <?php echo $todaysDate = date("m-d-Y");?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">All Student in Class</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->


              <!-- Input Group -->
        <form method="post" name="attendanceForm">
            <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Student in (<?php echo $rrw['className'].' - '.$rrw['classArmName'];?>) Class</h6>
                  <h6 class="m-0 font-weight-bold text-danger">Note: <i>Click on the checkboxes besides each student to take attendance!</i></h6>
                </div>
                <div class="table-responsive p-3">
                <div id="statusMsg"><?php echo $statusMsg; ?></div>
                  <table class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Other Name</th>
                        <th>Lrn</th>
                        <th>Class</th>
                        <th>Class Arm</th>
                        <th>Check</th>
                      </tr>
                    </thead>
                    
                    <tbody>

                  <?php
                      $dateTaken = date("Y-m-d"); // Format: 2025-03-02 (Only Date)
                      $query = "SELECT tblstudents.Id, tblstudents.Lrn, tblclass.className, 
                                       tblclass.Id AS classId, tblclassarms.classArmName, 
                                       tblclassarms.Id AS classArmId, tblstudents.firstName, 
                                       tblstudents.lastName, tblstudents.otherName, tblstudents.dateCreated
                                FROM tblstudents
                                INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                                INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                                LEFT JOIN tblattendance ON tblattendance.Lrn = tblstudents.Lrn 
                                                         AND DATE(tblattendance.dateTimeTaken) = '$dateTaken'
                                WHERE tblstudents.classId = '$_SESSION[classId]' 
                                AND tblstudents.classArmId = '$_SESSION[classArmId]' 
                                AND tblattendance.Lrn IS NULL";
                      
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;                      
                      $sn=0;
                      $status="";
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                             $sn = $sn + 1;
                            echo"
                              <tr>
                                <td>".$sn."</td>
                                <td>".$rows['firstName']."</td>
                                <td>".$rows['lastName']."</td>
                                <td>".$rows['otherName']."</td>
                                <td>".$rows['Lrn']."</td>
                                <td>".$rows['className']."</td>
                                <td>".$rows['classArmName']."</td>
                                <td><input name='check[]' type='checkbox' value=".$rows['Lrn']." class='form-control'></td>
                              </tr>";
                              echo "<input name='Lrn[]' value=".$rows['Lrn']." type='hidden' class='form-control'>";
                          }
                      }
                      else
                      {
                           echo   
                           "<div class='alert alert-danger' role='alert'>
                            No Record Found!
                            </div>";
                      }
                      
                      ?>
                    </tbody>
                  </table>
                  <br>
                  <button type="submit" name="save" class="btn btn-primary">Take Attendance</button>
                  <button type="submit" name="photo" class="btn btn-primary">Photo Attendance</button>
                  <button type="submit" name="qr" class="btn btn-primary">QR Attendance</button>
                  <button type="submit" name="lock" class="btn btn-danger">Lock Attendance</button>
                  </form>
                </div>
              </div>
            </div>
            </div>
          </div>
          <!--Row-->

          <!-- Documentation Link -->
          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>For more documentations you can visit<a href="https://getbootstrap.com/docs/4.3/components/forms/"
                  target="_blank">
                  bootstrap forms documentations.</a> and <a
                  href="https://getbootstrap.com/docs/4.3/components/input-group/" target="_blank">bootstrap input
                  groups documentations</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
   <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>