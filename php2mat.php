<?php
// ----------------------------------------------------------------
//
// php2mat.php
//
// save php data in a MATLAB® binary file
// see www.mathworks.com/contact_TS.html	
// based on Release 14SP3 of:
// http://www.mathworks.com/access/helpdesk/help/pdf_doc/matlab/matfile_format.pdf
// MATLAB is a registered trademark of The MathWorks, Inc.
//
// 15/06/2006 - Lucio.Zambon@gmail.com - First release
//
// ----------------------------------------------------------------
/*
	TODO list, 'x' means already done:
		x save an array of numerical variables into MAT4 format
		x add support for MAT5 format
		x add support for sending data bunch by bunch (MAT5 format only)
		add variables check
		add support for other types of variables and n-dimensional arrays 
		save gzip compressed data
	LICENSE: LGPL
	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License (LGPL) as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.
	
	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
	Lesser General Public License for more details.
	
	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA,
	or see http://www.gnu.org/copyleft/lesser.html
*/
	define("VERSION", "0.1.0");
	define("NAMELENGTHMAX", 63);
	// define("NAMELENGTHMAX", 31);
	// see http://www.mathworks.com/access/helpdesk/help/techdoc/ref/namelengthmax.html
	// uncomment the following line in order to save in MAT4 format
	// define("MAT4", true);
class php2mat {
	private $dataType = array(
		"miINT8" =>			 1, // 8 bit, signed
		"miUINT8" =>		 2, // 8 bit, unsigned
		"miINT16" =>		 3, // 16 bit, signed
		"miUINT16" =>		 4, // 16 bit, unsigned
		"miINT32" =>		 5, // 32 bit, signed
		"miUINT32" =>		 6, // 32 bit, unsigned
		"miSINGLE" =>		 7, // IEEE 754 single format
		"miDOUBLE" =>		 9, // IEEE 754 double format
		"miINT64" =>		12, // 64 bit, signed
		"miUINT64" =>		13, // 64 bit, unsigned
		"miMATRIX" =>		14, // MATLAB array
		"miCOMPRESSED" =>	15, // Compressed Data
		"miUTF8" =>			16, // UNICODE UTF-8 Encoded Charecter Data
		"miUTF16" =>		17, // UNICODE UTF-16 Encoded Charecter Data
		"miUTF32" =>		18  // UNICODE UTF-32 Encoded Charecter Data
	);
	private $classes = array(
		"mxCELL_CLASS" =>	 1, // Cell array
		"mxSTRUCT_CLASS" =>	 2, // Structure
		"mxOBJECT_CLASS" =>	 3, // Object
		"mxCHAR_CLASS" =>	 4, // Character array
		"mxSPARSE_CLASS" =>	 5, // Sparse array
		"mxDOUBLE_CLASS" =>	 6, // Double precision array
		"mxSINGLE_CLASS" =>	 7, // Single precisio array
		"mxINT8_CLASS" =>	 8, // 8-bit, signed integer
		"mxUINT8_CLASS" =>	 9, // 8-bit, unsigned integer
		"mxINT16_CLASS" =>	10, // 16-bit, signed integer
		"mxUINT16_CLASS" => 11, // 16-bit, unsigned integer
		"mxINT32_CLASS" =>	12, // 32-bit, signed integer
		"mxUINT32_CLASS" => 13  // 32-bit, unsigned integer
	);

		// ----------------------------------------------------------------
	// test if the server is BIG ENDIAN
	function is_bigendian() {
		list($endiantest) = array_values(unpack ('L1L', pack ('V',1)));
		return $endiantest != 1;
	}

	// ----------------------------------------------------------------
	// save some php arrays to a MAT4 matlab file
	function php2mat4($file, $varArray) {
		$data = "";
		foreach ($varArray as $n => $var) {
			$name = substr(strtr($n, array(" "=>"_","."=>"_")), 0, NAMELENGTHMAX);
			// setup header
			$header["type"] = $this->is_bigendian()? 1: 0;
			$header["mrows"] = count($var);
			$header["ncols"] = 1;
			$header["imagf"] = 0;
			$header["namelen"] = strlen($name) + 1;
			// write header
			foreach ($header as $h) {
				$data .= pack('V', $h);
			}
			// write variable name
			$data .= $name.pack('x');
			// write data
			if (is_array($var)) {
				foreach ($var as $v) {
					$data .= pack('d', $v);
				}
			}
			else {
				$data .= pack('d', $var);
			}
		}
		return $data;
	}

	// ----------------------------------------------------------------
	// add a php array row to a MAT5 matlab file
	function php2mat5_var_addrow($v) {
		if (is_array($v)) foreach ($v as $k) {
			echo pack('d', $k);
		}
		else {
			echo pack('d', $v);
		}
	}

	// ----------------------------------------------------------------
	// add a php array header to a MAT5 matlab file
	function php2mat5_var_init($name, $cols, $rows) {
		// write header
		echo pack('L', $this->dataType["miMATRIX"]);
		$buf = pack('LL', $this->dataType["miUINT32"], 8);
		$buf .= pack('LL', $this->classes["mxDOUBLE_CLASS"], 0);
		// write Dimensions Array
		$buf .= pack('LL', $this->dataType["miINT32"], 8);
		$buf .= pack('LL', $cols, $rows);
		// write Array Name
		$name = substr(strtr($name, array(" "=>"_","."=>"_")), 0, NAMELENGTHMAX);
		$len = strlen($name);
		if ($len <= 4) {
			$buf .= pack('SS', $this->dataType["miINT8"], $len);
			$buf .= $name.pack('@'.((4 - $len) % 8));
		}
		else if ($len <= 32) {
			$buf .= pack('LL', $this->dataType["miINT8"], $len);
			$buf .= $name.pack('@'.((32 - $len) % 8));
		}
		else {
			$buf .= pack('LL', $this->dataType["miINT16"], $len);
			$buf .= $name.pack('@'.((64 - $len) % 8));
		}
		// write Real Part
		$len = $cols*$rows*8;
		$buf .= pack('LL', $this->dataType["miDOUBLE"], $len);
		// write buffered data size
		echo pack('L', strlen($buf)+$len);
		echo $buf;
	}

	// ----------------------------------------------------------------
	// save some php arrays to a MAT5 matlab file
	function php2mat5_head($fileName='php2mat.mat', $headerText=null) {
		header("Content-Disposition: attachment; filename=$fileName");
		header("Content-Type: application/x-matlab");
		$name = substr("Library: php2mat.php, $headerText", 0, 115);
		$len = 116 - strlen($name);
		// write Descriptive Text
		echo pack('A116', $name);
		// write the rest of header
		echo "        ".pack('v', 0x0100).($this->is_bigendian()? "MI": "IM");
	}

	// ----------------------------------------------------------------
	// save some php arrays to a MAT5 matlab file
	function php2mat5($file, $varArray, $headerText) {
		$data = "";
		$name = substr("Library: php2mat.php, $headerText", 0, 115);
		$len = 116 - strlen($name);
		// write Descriptive Text
		$data .= pack('A116', $name);
		// write the rest of header
		$data .= "        ".pack('v', 0x0100).($this->is_bigendian()? "MI": "IM");
		foreach ($varArray as $name => $var) {
			// write header
			$data .= pack('L', $this->dataType["miMATRIX"]);
			$buf = pack('LL', $this->dataType["miUINT32"], 8);
			$buf .= pack('LL', $this->classes["mxDOUBLE_CLASS"], 0);
			// write Dimensions Array
			$buf .= pack('LL', $this->dataType["miINT32"], 8);
			$buf .= pack('LL', count($var[0]), count($var));
			// write Array Name
			$name = substr(strtr($name, array(" "=>"_","."=>"_")), 0, NAMELENGTHMAX);
			$len = strlen($name);
			if ($len <= 4) {
				$buf .= pack('SS', $this->dataType["miINT8"], $len);
				$buf .= $name.pack('@'.((4 - $len) % 8));
			}
			else if ($len <= 32) {
				$buf .= pack('LL', $this->dataType["miINT8"], $len);
				$buf .= $name.pack('@'.((32 - $len) % 8));
			}
			else {
				$buf .= pack('LL', $this->dataType["miINT16"], $len);
				$buf .= $name.pack('@'.((64 - $len) % 8));
			}
			// write Real Part
			$buf .= pack('LL', $this->dataType["miDOUBLE"], count($var)*count($var[0])*8);
			// write data
			if (is_array($var)) {
				foreach ($var as $v) {
					if (is_array($v)) foreach ($v as $k) {
						$buf .= pack('d', $k);
					}
					else {
						$buf .= pack('d', $v);
					}
				}
			}
			else {
				$buf .= pack('d', $var);
			}
			// write buffered data size
			$data .= pack('L', strlen($buf));
			// write buffered data
			$data .= $buf;
		}
		// echo "<pre>"; print_r($data); echo "</pre>"; exit();
		return $data;
	}

	// ----------------------------------------------------------------
	// save some php arrays to a matlab file
	function generate($file, $varArray, $headerText=null) {
		if (defined("MAT4")) {
			return $this->php2mat4($file, $varArray);
		}
		else {
			return $this->php2mat5($file, $varArray, $headerText);
		}
	}

	// ----------------------------------------------------------------
	// save some php arrays to a matlab file
	function save($file, $varArray, $headerText=null) {
		if (defined("MAT4")) {
			$buf = $this->php2mat4($file, $varArray);
		}
		else {
			$buf = $this->php2mat5($file, $varArray, $headerText);
		}
		// open file for reading
		if (($fp = fopen($file, "wb")) === false) {
			return false;
		}
		fwrite($fp, $buf);
		fclose($fp);
		return true;
	}

	// ----------------------------------------------------------------
	// sand .mat file directly to browser
	function SendFile($fileName, $data, $title) {
		$buf = $this->generate($fileName, $data, $title);
		header("Content-Disposition: attachment; filename=$fileName");
		header("Content-Type: application/x-matlab");
		header("Content-Length: ".strlen($buf));
		echo $buf;
	}
}

// EXAMPLES 
if (isset($_REQUEST['test_php2mat'])) {
	// test this library
	$php2mat = new php2mat();
	// eval platform
	list($h, $platform, $g) = explode(";", strtr($_SERVER["HTTP_USER_AGENT"], array("(" => ";")), 3);
	if ($_REQUEST['test_php2mat']==1) {
		$php2mat->php2mat5_head('mydata.mat', "<my_application>, Platform: $platform");
		$data = array("magic"=>array(1, 0, 9, 3, 7, 6),"a_really_very_very_long_variable_name"=>array(array(1, 2, 3, 4, 5, 6),array(1, 0, 9, 3, 7,6)));
		foreach ($data as $key=>$d) {
			$php2mat->php2mat5_var_init($key, count($d[0]), count($d));
			foreach ($d as $v) {
				$php2mat->php2mat5_var_addrow($v);
			}
		}
		exit();
	}
	else if ($_REQUEST['test_php2mat']==2) {
		$php2mat->php2mat5_head('mydata.mat', 'does it work?');
		$data = array(1, 0, 9, 3, 7, 6);
		$php2mat->php2mat5_var_init($key, count($data), 1);
		foreach ($data as $v) {
			$php2mat->php2mat5_var_addrow($v);
		}
		exit();
	}
	else if ($_REQUEST['test_php2mat']==15) 
		$php2mat->SendFile("test15.mat", array("magic"=>array(1, 0, 9, 3, 7, 6),"a_really_very_very_long_variable_name"=>array(array(1, 2, 3, 4, 5, 6),array(1, 0, 9, 3, 7,6))), "<my_application>, Platform: $platform");
	else if ($_REQUEST['test_php2mat']==5)
		if ($php2mat->save("test5.mat", array("magic"=>array(1, 0, 9, 3, 7, 6), "x"=>1, "y"=>3.14), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"))) {
			echo "done";
		}
		else {
			echo "ERROR: cannot open file";
		}
	else if ($_REQUEST['test_php2mat']==10)
		$php2mat->save("test10.mat", array("x"=>1.23), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"));
	else if ($_REQUEST['test_php2mat']==11)
		 $php2mat->save("test11.mat", array("xxx"=>1), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"));
	else if ($_REQUEST['test_php2mat']==12)
		$php2mat->save("test12.mat", array("xxxx"=>1.23), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"));
	else if ($_REQUEST['test_php2mat']==13)
		$php2mat->save("test13.mat", array("xxxxx"=>1.23), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"));
	else if ($_REQUEST['test_php2mat']==14)
		$php2mat->save("test14.mat", array("magic_x"=>1, "magic_y"=>3.14), "<my_application>, Platform: $platform, Created on: ".date("d-F-Y H:i:s"));
}