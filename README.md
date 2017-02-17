# php2mat
export data from PHP to MAT format

save php data in a MATLAB® binary file
based on Release 14SP3 of:

http://www.mathworks.com/access/helpdesk/help/pdf_doc/matlab/matfile_format.pdf

# Usage
require('php2mat.php');

$php2mat = new php2mat();


$my_array = array("magic"=>array(1, 0, 9, 3, 7, 6),"a_really_very_very_long_variable_name"=>array(array(1, 2, 3, 4, 5, 6),array(1, 0, 9, 3, 7,6)));

$php2mat->SendFile("test.mat", $my_array, "<my_text>");

/* 

extracting data from database MySQL and saving

into MAT file bunch by bunch, this method 

consumes much less memory than using 

$php2mat->SendFile() after having loaded all data.

*/

$php2mat->php2mat5_head('test.mat', "<my_text>");

$res = mysqli_query($db, 'SELECT value FROM table');

$php2mat->php2mat5_var_init('variable_name', 1, mysqli_num_rows($res));

while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

    $php2mat->php2mat5_var_addrow($row['value']);

}


MATLAB is a registered trademark of The MathWorks, Inc.

MySQL is a registered trademark of Oracle, Inc.
