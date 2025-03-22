<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

?>
        <table border="1">
        <thead>
            <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Other Name</th>
            <th>Lrn</th>
            <th>Class</th>
            <th>Class Arm</th>
            <th>Status</th>
            <th>Date</th>
            </tr>
        </thead>

<?php 
$filename="Attendance list";
$dateTaken = date("Y-m-d");

$cnt=1;			
$ret = mysqli_query($conn,"SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, 
        tblclass.className, tblclassarms.classArmName, 
        tblstudents.firstName, tblstudents.lastName, tblstudents.otherName, tblstudents.Lrn
        FROM tblattendance
        INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
        INNER JOIN tblstudents ON tblstudents.Lrn = tblattendance.Lrn
        WHERE DATE(tblattendance.dateTimeTaken) = '$dateTaken' 
        AND tblattendance.classId = '$_SESSION[classId]' 
        AND tblattendance.classArmId = '$_SESSION[classArmId]'");

if(mysqli_num_rows($ret) > 0 )
{
while ($row=mysqli_fetch_array($ret)) 
{ 
    if($row['status'] === '1'){$status = "Present";}
    else if($row['status'] === '3'){$status = "Late";}
    else{$status = "Absent";}

echo '  
<tr>  
<td>'.$cnt.'</td> 
<td>'.$firstName= $row['firstName'].'</td> 
<td>'.$lastName= $row['lastName'].'</td> 
<td>'.$otherName= $row['otherName'].'</td> 
<td>'.$Lrn= $row['Lrn'].'</td> 
<td>'.$className= $row['className'].'</td> 
<td>'.$classArmName=$row['classArmName'].'</td>	
<td>'.$status=$status.'</td>	 	
<td>'.$dateTimeTaken=$row['dateTimeTaken'].'</td>	 					
</tr>  
';
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=".$filename."-report.xls");
header("Pragma: no-cache");
header("Expires: 0");
			$cnt++;
			}
	}
?>
</table>