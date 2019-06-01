<?php

/**
 * A class for reading Microsoft Excel (97/2003) Spreadsheets.
 *
 * Version 2.21
 *
 * Enhanced and maintained by Matt Kruse < http://mattkruse.com >
 * Maintained at http://code.google.com/p/php-excel-reader/
 *
 * Format parsing and MUCH more contributed by:
 *    Matt Roxburgh < http://www.roxburgh.me.uk >
 *
 * DOCUMENTATION
 * =============
 *   http://code.google.com/p/php-excel-reader/wiki/Documentation
 *
 * CHANGE LOG
 * ==========
 *   http://code.google.com/p/php-excel-reader/wiki/ChangeHistory
 *
 * DISCUSSION/SUPPORT
 * ==================
 *   http://groups.google.com/group/php-excel-reader-discuss/topics
 *
 * --------------------------------------------------------------------------
 *
 * Originally developed by Vadim Tkachenko under the name PHPExcelReader.
 * (http://sourceforge.net/projects/phpexcelreader)
 * Based on the Java version by Andy Khan (http://www.andykhan.com).  Now
 * maintained by David Sanders.  Reads only Biff 7 and Biff 8 formats.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Spreadsheet
 * @package	Spreadsheet_Excel_Reader
 * @author	 Vadim Tkachenko <vt@apachephp.com>
 * @license	http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version	CVS: $Id: reader.php 19 2007-03-13 12:42:41Z shangxiao $
 * @link	   http://pear.php.net/package/Spreadsheet_Excel_Reader
 * @see		OLE, Spreadsheet_Excel_Writer
 * --------------------------------------------------------------------------
 */
define('NUM_BIG_BLOCK_DEPOT_BLOCKS_POS', 0x2c);
define('SMALL_BLOCK_DEPOT_BLOCK_POS', 0x3c);
define('ROOT_START_BLOCK_POS', 0x30);
define('BIG_BLOCK_SIZE', 0x200);
define('SMALL_BLOCK_SIZE', 0x40);
define('EXTENSION_BLOCK_POS', 0x44);
define('NUM_EXTENSION_BLOCK_POS', 0x48);
define('PROPERTY_STORAGE_BLOCK_SIZE', 0x80);
define('BIG_BLOCK_DEPOT_BLOCKS_POS', 0x4c);
define('SMALL_BLOCK_THRESHOLD', 0x1000);
// property storage offsets
define('SIZE_OF_NAME_POS', 0x40);
define('TYPE_POS', 0x42);
define('START_BLOCK_POS', 0x74);
define('SIZE_POS', 0x78);
define('IDENTIFIER_OLE', pack("CCCCCCCC", 0xd0, 0xcf, 0x11, 0xe0, 0xa1, 0xb1, 0x1a, 0xe1));

function GetInt4d($data, $pos) {
    $value = ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | (ord($data[$pos + 3]) << 24);
    if ($value >= 4294967294) {
        $value = -2;
    }
    return $value;
}

// http://uk.php.net/manual/en/function.getdate.php
function gmgetdate($ts = null) {
    $k = array('seconds', 'minutes', 'hours', 'mday', 'wday', 'mon', 'year', 'yday', 'weekday', 'month', 0);
    return(array_comb($k, explode(":", gmdate('s:i:G:j:w:n:Y:z:l:F:U', is_null($ts) ? time() : $ts))));
}

// Added for PHP4 compatibility
function array_comb($array1, $array2) {
    $out = array();
    foreach ($array1 as $key => $value) {
        $out[$value] = $array2[$key];
    }
    return $out;
}

function v($data, $pos) {
    return ord($data[$pos]) | ord($data[$pos + 1]) << 8;
}

class Oleread {

    var $data = '';

    function Oleread() {
        
    }

    function read($sFileName) {
        // check if file exist and is readable (Darko Miljanovic)
        if (!is_readable($sFileName)) {
            $this->error = 1;
            return false;
        }
        $this->data = @file_get_contents($sFileName);
        if (!$this->data) {
            $this->error = 1;
            return false;
        }
        if (substr($this->data, 0, 8) != IDENTIFIER_OLE) {
            $this->error = 1;
            return false;
        }
        $this->numBigBlockDepotBlocks = GetInt4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
        $this->sbdStartBlock = GetInt4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
        $this->rootStartBlock = GetInt4d($this->data, ROOT_START_BLOCK_POS);
        $this->extensionBlock = GetInt4d($this->data, EXTENSION_BLOCK_POS);
        $this->numExtensionBlocks = GetInt4d($this->data, NUM_EXTENSION_BLOCK_POS);

        $bigBlockDepotBlocks = array();
        $pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
        $bbdBlocks = $this->numBigBlockDepotBlocks;
        if ($this->numExtensionBlocks != 0) {
            $bbdBlocks = (BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS) / 4;
        }

        for ($i = 0; $i < $bbdBlocks; $i++) {
            $bigBlockDepotBlocks[$i] = GetInt4d($this->data, $pos);
            $pos += 4;
        }


        for ($j = 0; $j < $this->numExtensionBlocks; $j++) {
            $pos = ($this->extensionBlock + 1) * BIG_BLOCK_SIZE;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, BIG_BLOCK_SIZE / 4 - 1);

            for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++) {
                $bigBlockDepotBlocks[$i] = GetInt4d($this->data, $pos);
                $pos += 4;
            }

            $bbdBlocks += $blocksToRead;
            if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
                $this->extensionBlock = GetInt4d($this->data, $pos);
            }
        }

        // readBigBlockDepot
        $pos = 0;
        $index = 0;
        $this->bigBlockChain = array();

        for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++) {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * BIG_BLOCK_SIZE;
            //echo "pos = $pos";
            for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++) {
                $this->bigBlockChain[$index] = GetInt4d($this->data, $pos);
                $pos += 4;
                $index++;
            }
        }

        // readSmallBlockDepot();
        $pos = 0;
        $index = 0;
        $sbdBlock = $this->sbdStartBlock;
        $this->smallBlockChain = array();

        while ($sbdBlock != -2) {
            $pos = ($sbdBlock + 1) * BIG_BLOCK_SIZE;
            for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++) {
                $this->smallBlockChain[$index] = GetInt4d($this->data, $pos);
                $pos += 4;
                $index++;
            }
            $sbdBlock = $this->bigBlockChain[$sbdBlock];
        }


        // readData(rootStartBlock)
        $block = $this->rootStartBlock;
        $pos = 0;
        $this->entry = $this->__readData($block);
        $this->__readPropertySets();
    }

    function __readData($bl) {
        $block = $bl;
        $pos = 0;
        $data = '';
        while ($block != -2) {
            $pos = ($block + 1) * BIG_BLOCK_SIZE;
            $data = $data . substr($this->data, $pos, BIG_BLOCK_SIZE);
            $block = $this->bigBlockChain[$block];
        }
        return $data;
    }

    function __readPropertySets() {
        $offset = 0;
        while ($offset < strlen($this->entry)) {
            $d = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
            $nameSize = ord($d[SIZE_OF_NAME_POS]) | (ord($d[SIZE_OF_NAME_POS + 1]) << 8);
            $type = ord($d[TYPE_POS]);
            $startBlock = GetInt4d($d, START_BLOCK_POS);
            $size = GetInt4d($d, SIZE_POS);
            $name = '';
            for ($i = 0; $i < $nameSize; $i++) {
                $name .= $d[$i];
            }
            $name = str_replace("\x00", "", $name);
            $this->props[] = array(
                'name' => $name,
                'type' => $type,
                'startBlock' => $startBlock,
                'size' => $size);
            if ((strtolower($name) == "workbook") || ( strtolower($name) == "book")) {
                $this->wrkbook = count($this->props) - 1;
            }
            if ($name == "Root Entry") {
                $this->rootentry = count($this->props) - 1;
            }
            $offset += PROPERTY_STORAGE_BLOCK_SIZE;
        }
    }

    function getWorkBook() {
        if ($this->props[$this->wrkbook]['size'] < SMALL_BLOCK_THRESHOLD) {
            $rootdata = $this->__readData($this->props[$this->rootentry]['startBlock']);
            $streamData = '';
            $block = $this->props[$this->wrkbook]['startBlock'];
            $pos = 0;
            while ($block != -2) {
                $pos = $block * SMALL_BLOCK_SIZE;
                $streamData .= substr($rootdata, $pos, SMALL_BLOCK_SIZE);
                $block = $this->smallBlockChain[$block];
            }
            return $streamData;
        } else {
            $numBlocks = $this->props[$this->wrkbook]['size'] / BIG_BLOCK_SIZE;
            if ($this->props[$this->wrkbook]['size'] % BIG_BLOCK_SIZE != 0) {
                $numBlocks++;
            }

            if ($numBlocks == 0)
                return '';
            $streamData = '';
            $block = $this->props[$this->wrkbook]['startBlock'];
            $pos = 0;
            while ($block != -2) {
                $pos = ($block + 1) * BIG_BLOCK_SIZE;
                $streamData .= substr($this->data, $pos, BIG_BLOCK_SIZE);
                $block = $this->bigBlockChain[$block];
            }
            return $streamData;
        }
    }

}

define('SPREADSHEET_EXCEL_READER_BIFF8', 0x600);
define('SPREADSHEET_EXCEL_READER_BIFF7', 0x500);
define('SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS', 0x5);
define('SPREADSHEET_EXCEL_READER_WORKSHEET', 0x10);
define('SPREADSHEET_EXCEL_READER_TYPE_BOF', 0x809);
define('SPREADSHEET_EXCEL_READER_TYPE_EOF', 0x0a);
define('SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET', 0x85);
define('SPREADSHEET_EXCEL_READER_TYPE_DIMENSION', 0x200);
define('SPREADSHEET_EXCEL_READER_TYPE_ROW', 0x208);
define('SPREADSHEET_EXCEL_READER_TYPE_DBCELL', 0xd7);
define('SPREADSHEET_EXCEL_READER_TYPE_FILEPASS', 0x2f);
define('SPREADSHEET_EXCEL_READER_TYPE_NOTE', 0x1c);
define('SPREADSHEET_EXCEL_READER_TYPE_TXO', 0x1b6);
define('SPREADSHEET_EXCEL_READER_TYPE_RK', 0x7e);
define('SPREADSHEET_EXCEL_READER_TYPE_RK2', 0x27e);
define('SPREADSHEET_EXCEL_READER_TYPE_MULRK', 0xbd);
define('SPREADSHEET_EXCEL_READER_TYPE_MULBLANK', 0xbe);
define('SPREADSHEET_EXCEL_READER_TYPE_INDEX', 0x20b);
define('SPREADSHEET_EXCEL_READER_TYPE_SST', 0xfc);
define('SPREADSHEET_EXCEL_READER_TYPE_EXTSST', 0xff);
define('SPREADSHEET_EXCEL_READER_TYPE_CONTINUE', 0x3c);
define('SPREADSHEET_EXCEL_READER_TYPE_LABEL', 0x204);
define('SPREADSHEET_EXCEL_READER_TYPE_LABELSST', 0xfd);
define('SPREADSHEET_EXCEL_READER_TYPE_NUMBER', 0x203);
define('SPREADSHEET_EXCEL_READER_TYPE_NAME', 0x18);
define('SPREADSHEET_EXCEL_READER_TYPE_ARRAY', 0x221);
define('SPREADSHEET_EXCEL_READER_TYPE_STRING', 0x207);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA', 0x406);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA2', 0x6);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMAT', 0x41e);
define('SPREADSHEET_EXCEL_READER_TYPE_XF', 0xe0);
define('SPREADSHEET_EXCEL_READER_TYPE_BOOLERR', 0x205);
define('SPREADSHEET_EXCEL_READER_TYPE_FONT', 0x0031);
define('SPREADSHEET_EXCEL_READER_TYPE_PALETTE', 0x0092);
define('SPREADSHEET_EXCEL_READER_TYPE_UNKNOWN', 0xffff);
define('SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR', 0x22);
define('SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS', 0xE5);
define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS', 25569);
define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904', 24107);
define('SPREADSHEET_EXCEL_READER_MSINADAY', 86400);
define('SPREADSHEET_EXCEL_READER_TYPE_HYPER', 0x01b8);
define('SPREADSHEET_EXCEL_READER_TYPE_COLINFO', 0x7d);
define('SPREADSHEET_EXCEL_READER_TYPE_DEFCOLWIDTH', 0x55);
define('SPREADSHEET_EXCEL_READER_TYPE_STANDARDWIDTH', 0x99);
define('SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT', "%s");


/*
 * Main Class
 */

class Spreadsheet_Excel_Reader {

    // MK: Added to make data retrieval easier
    var $colnames = array();
    var $colindexes = array();
    var $standardColWidth = 0;
    var $defaultColWidth = 0;
    var $CI;

    function myHex($d) {
        if ($d < 16)
            return "0" . dechex($d);
        return dechex($d);
    }

    function dumpHexData($data, $pos, $length) {
        $info = "";
        for ($i = 0; $i <= $length; $i++) {
            $info .= ($i == 0 ? "" : " ") . $this->myHex(ord($data[$pos + $i])) . (ord($data[$pos + $i]) > 31 ? "[" . $data[$pos + $i] . "]" : '');
        }
        return $info;
    }

    function getCol($col) {
        if (is_string($col)) {
            $col = strtolower($col);
            if (array_key_exists($col, $this->colnames)) {
                $col = $this->colnames[$col];
            }
        }
        return $col;
    }

    // PUBLIC API FUNCTIONS
    // --------------------

    function val($row, $col, $sheet = 0) {
        $col = $this->getCol($col);
        if (array_key_exists($row, $this->sheets[$sheet]['cells']) && array_key_exists($col, $this->sheets[$sheet]['cells'][$row])) {
            return $this->sheets[$sheet]['cells'][$row][$col];
        }
        return "";
    }

    function value($row, $col, $sheet = 0) {
        return $this->val($row, $col, $sheet);
    }

    function info($row, $col, $type = '', $sheet = 0) {
        $col = $this->getCol($col);
        if (array_key_exists('cellsInfo', $this->sheets[$sheet]) && array_key_exists($row, $this->sheets[$sheet]['cellsInfo']) && array_key_exists($col, $this->sheets[$sheet]['cellsInfo'][$row]) && array_key_exists($type, $this->sheets[$sheet]['cellsInfo'][$row][$col])) {
            return $this->sheets[$sheet]['cellsInfo'][$row][$col][$type];
        }
        return "";
    }

    function type($row, $col, $sheet = 0) {
        return $this->info($row, $col, 'type', $sheet);
    }

    function raw($row, $col, $sheet = 0) {
        return $this->info($row, $col, 'raw', $sheet);
    }

    function rowspan($row, $col, $sheet = 0) {
        $val = $this->info($row, $col, 'rowspan', $sheet);
        if ($val == "") {
            return 1;
        }
        return $val;
    }

    function colspan($row, $col, $sheet = 0) {
        $val = $this->info($row, $col, 'colspan', $sheet);
        if ($val == "") {
            return 1;
        }
        return $val;
    }

    function hyperlink($row, $col, $sheet = 0) {
        $link = $this->sheets[$sheet]['cellsInfo'][$row][$col]['hyperlink'];
        if ($link) {
            return $link['link'];
        }
        return '';
    }

    function rowcount($sheet = 0) {
        return $this->sheets[$sheet]['numRows'];
    }

    function colcount($sheet = 0) {
        return $this->sheets[$sheet]['numCols'];
    }

    function colwidth($col, $sheet = 0) {
        // Col width is actually the width of the number 0. So we have to estimate and come close
        return $this->colInfo[$sheet][$col]['width'] / 9142 * 200;
    }

    function colhidden($col, $sheet = 0) {
        return !!$this->colInfo[$sheet][$col]['hidden'];
    }

    function rowheight($row, $sheet = 0) {
        return $this->rowInfo[$sheet][$row]['height'];
    }

    function rowhidden($row, $sheet = 0) {
        return !!$this->rowInfo[$sheet][$row]['hidden'];
    }

    // GET THE CSS FOR FORMATTING
    // ==========================
    function style($row, $col, $sheet = 0, $properties = '') {
        $css = "";
        $font = $this->font($row, $col, $sheet);
        if ($font != "") {
            $css .= "font-family:$font;";
        }
        $align = $this->align($row, $col, $sheet);
        if ($align != "") {
            $css .= "text-align:$align;";
        }
        $height = $this->height($row, $col, $sheet);
        if ($height != "") {
            $css .= "font-size:$height" . "px;";
        }
        $bgcolor = $this->bgColor($row, $col, $sheet);
        if ($bgcolor != "") {
            $bgcolor = $this->colors[$bgcolor];
            $css .= "background-color:$bgcolor;";
        }
        $color = $this->color($row, $col, $sheet);
        if ($color != "") {
            $css .= "color:$color;";
        }
        $bold = $this->bold($row, $col, $sheet);
        if ($bold) {
            $css .= "font-weight:bold;";
        }
        $italic = $this->italic($row, $col, $sheet);
        if ($italic) {
            $css .= "font-style:italic;";
        }
        $underline = $this->underline($row, $col, $sheet);
        if ($underline) {
            $css .= "text-decoration:underline;";
        }
        // Borders
        $bLeft = $this->borderLeft($row, $col, $sheet);
        $bRight = $this->borderRight($row, $col, $sheet);
        $bTop = $this->borderTop($row, $col, $sheet);
        $bBottom = $this->borderBottom($row, $col, $sheet);
        $bLeftCol = $this->borderLeftColor($row, $col, $sheet);
        $bRightCol = $this->borderRightColor($row, $col, $sheet);
        $bTopCol = $this->borderTopColor($row, $col, $sheet);
        $bBottomCol = $this->borderBottomColor($row, $col, $sheet);
        // Try to output the minimal required style
        if ($bLeft != "" && $bLeft == $bRight && $bRight == $bTop && $bTop == $bBottom) {
            $css .= "border:" . $this->lineStylesCss[$bLeft] . ";";
        } else {
            if ($bLeft != "") {
                $css .= "border-left:" . $this->lineStylesCss[$bLeft] . ";";
            }
            if ($bRight != "") {
                $css .= "border-right:" . $this->lineStylesCss[$bRight] . ";";
            }
            if ($bTop != "") {
                $css .= "border-top:" . $this->lineStylesCss[$bTop] . ";";
            }
            if ($bBottom != "") {
                $css .= "border-bottom:" . $this->lineStylesCss[$bBottom] . ";";
            }
        }
        // Only output border colors if there is an actual border specified
        if ($bLeft != "" && $bLeftCol != "") {
            $css .= "border-left-color:" . $bLeftCol . ";";
        }
        if ($bRight != "" && $bRightCol != "") {
            $css .= "border-right-color:" . $bRightCol . ";";
        }
        if ($bTop != "" && $bTopCol != "") {
            $css .= "border-top-color:" . $bTopCol . ";";
        }
        if ($bBottom != "" && $bBottomCol != "") {
            $css .= "border-bottom-color:" . $bBottomCol . ";";
        }

        return $css;
    }

    // FORMAT PROPERTIES
    // =================
    function format($row, $col, $sheet = 0) {
        return $this->info($row, $col, 'format', $sheet);
    }

    function formatIndex($row, $col, $sheet = 0) {
        return $this->info($row, $col, 'formatIndex', $sheet);
    }

    function formatColor($row, $col, $sheet = 0) {
        return $this->info($row, $col, 'formatColor', $sheet);
    }

    // CELL (XF) PROPERTIES
    // ====================
    function xfRecord($row, $col, $sheet = 0) {
        $xfIndex = $this->info($row, $col, 'xfIndex', $sheet);
        if ($xfIndex != "") {
            return $this->xfRecords[$xfIndex];
        }
        return null;
    }

    function xfProperty($row, $col, $sheet, $prop) {
        $xfRecord = $this->xfRecord($row, $col, $sheet);
        if ($xfRecord != null) {
            return $xfRecord[$prop];
        }
        return "";
    }

    function align($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'align');
    }

    function bgColor($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'bgColor');
    }

    function borderLeft($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'borderLeft');
    }

    function borderRight($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'borderRight');
    }

    function borderTop($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'borderTop');
    }

    function borderBottom($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'borderBottom');
    }

    function borderLeftColor($row, $col, $sheet = 0) {
        return $this->colors[$this->xfProperty($row, $col, $sheet, 'borderLeftColor')];
    }

    function borderRightColor($row, $col, $sheet = 0) {
        return $this->colors[$this->xfProperty($row, $col, $sheet, 'borderRightColor')];
    }

    function borderTopColor($row, $col, $sheet = 0) {
        return $this->colors[$this->xfProperty($row, $col, $sheet, 'borderTopColor')];
    }

    function borderBottomColor($row, $col, $sheet = 0) {
        return $this->colors[$this->xfProperty($row, $col, $sheet, 'borderBottomColor')];
    }

    // FONT PROPERTIES
    // ===============
    function fontRecord($row, $col, $sheet = 0) {
        $xfRecord = $this->xfRecord($row, $col, $sheet);
        if ($xfRecord != null) {
            $font = $xfRecord['fontIndex'];
            if ($font != null) {
                return $this->fontRecords[$font];
            }
        }
        return null;
    }

    function fontProperty($row, $col, $sheet = 0, $prop) {
        $font = $this->fontRecord($row, $col, $sheet);
        if ($font != null) {
            return $font[$prop];
        }
        return false;
    }

    function fontIndex($row, $col, $sheet = 0) {
        return $this->xfProperty($row, $col, $sheet, 'fontIndex');
    }

    function color($row, $col, $sheet = 0) {
        $formatColor = $this->formatColor($row, $col, $sheet);
        if ($formatColor != "") {
            return $formatColor;
        }
        $ci = $this->fontProperty($row, $col, $sheet, 'color');
        return $this->rawColor($ci);
    }

    function rawColor($ci) {
        if (($ci <> 0x7FFF) && ($ci <> '')) {
            return $this->colors[$ci];
        }
        return "";
    }

    function bold($row, $col, $sheet = 0) {
        return $this->fontProperty($row, $col, $sheet, 'bold');
    }

    function italic($row, $col, $sheet = 0) {
        return $this->fontProperty($row, $col, $sheet, 'italic');
    }

    function underline($row, $col, $sheet = 0) {
        return $this->fontProperty($row, $col, $sheet, 'under');
    }

    function height($row, $col, $sheet = 0) {
        return $this->fontProperty($row, $col, $sheet, 'height');
    }

    function font($row, $col, $sheet = 0) {
        return $this->fontProperty($row, $col, $sheet, 'font');
    }

    // DUMP AN HTML TABLE OF THE ENTIRE XLS DATA
    // =========================================


    function prefix_zeros($number) {
        $numbLength = strlen($number);
        switch ($numbLength) {
            case 1: $number = "0000" . $number;
                break;
            case 2: $number = "000" . $number;
                break;
            case 3: $number = "00" . $number;
                break;
            case 4: $number = "0" . $number;
                break;
            default : break;
        }
        return $number;
    }

    function format_cell_data($string) {
        if ($string == "NA")
            $string = "";

        return addslashes($string);
    }

    function v1_emp_dump($row_numbers = false, $col_letters = false, $sheet = 0, $table_class = 'excel') {

        $col_count = $this->rowcount($sheet);
        $error_msg = "";
        for ($row = 2; $row <= $this->rowcount($sheet); $row++) {

            $emp_name = $this->format_cell_data($this->val($row, 1, $sheet));
            if ($emp_name != "" && $emp_name != "NA") {


                $dob = $this->format_cell_data($this->val($row, 2, $sheet));
                $gender = $this->format_cell_data($this->val($row, 3, $sheet));
                $father = $this->format_cell_data($this->val($row, 4, $sheet));
                $mother = $this->format_cell_data($this->val($row, 5, $sheet));
                $mstatus = $this->format_cell_data($this->val($row, 6, $sheet));
                $blood_group = $this->format_cell_data($this->val($row, 7, $sheet));

                // Address Details
                $address = $this->format_cell_data($this->val($row, 8, $sheet));
                $country = $this->format_cell_data($this->val($row, 9, $sheet));
                $state = $this->format_cell_data($this->val($row, 10, $sheet));
                $town = $this->format_cell_data($this->val($row, 11, $sheet));
                $district = $this->format_cell_data($this->val($row, 12, $sheet));
                $pincode = $this->format_cell_data($this->val($row, 13, $sheet));


                // Account Info

                $pan = $this->format_cell_data($this->val($row, 14, $sheet));
                $pf = $this->format_cell_data($this->val($row, 15, $sheet));
                $esi = $this->format_cell_data($this->val($row, 16, $sheet));
                $bank = $this->format_cell_data($this->val($row, 17, $sheet));
                $accno = $this->format_cell_data($this->val($row, 18, $sheet));
                $ifsc = $this->format_cell_data($this->val($row, 19, $sheet));



                // Company Information

                $emp_code = $this->format_cell_data($this->val($row, 20, $sheet));
                $doj = $this->format_cell_data($this->val($row, 21, $sheet));
                $department = $this->format_cell_data($this->val($row, 22, $sheet));
                $designation = $this->format_cell_data($this->val($row, 23, $sheet));


                // Company Contact

                $company_mail = $this->format_cell_data($this->val($row, 24, $sheet));
                $company_contact = $this->format_cell_data($this->val($row, 25, $sheet));
                $experience = $this->format_cell_data($this->val($row, 26, $sheet));
                $report_to_id = $this->format_cell_data($this->val($row, 27, $sheet));


                // Personal Details

                $mobile = $this->format_cell_data($this->val($row, 28, $sheet));
                $email = $this->format_cell_data($this->val($row, 29, $sheet));


                $role_str = $this->format_cell_data($this->val($row, 30, $sheet));
                $emp_roles_array = explode(";", $role_str);





                $cluser = $this->format_cell_data($this->val($row, 31, $sheet));

                $new_comapny_array = array();
                $new_app_role_array = array();
                $comp_ids = array("NASL" => 1, "NATL" => 2);



                $desg_str = "";

                $branch_id_array = array();
                $desg_array = array();
                $company_id_array = array();
                $emp_roles = array();
                //      print_r($emp_roles_array);
                for ($jc = 0; $jc < count($emp_roles_array); $jc++) {
                    $sub_error_msg = "";
                    $obj = $emp_roles_array[$jc];
                    $oind_obj = explode(",", $obj);
                    $company_key = "";
                    $office = "";
                    $app_role1 = "";
                    if (count($oind_obj) == 3) {
                        if (isset($oind_obj[0]) && $oind_obj[0] != "NA" && $oind_obj[0] != "") {
                            $company_key = $oind_obj[0];
                        }
                        if (isset($oind_obj[1]) && $oind_obj[1] != "NA" && $oind_obj[1] != "") {
                            $office = $oind_obj[1];
                        }
                        if (isset($oind_obj[2]) && $oind_obj[2] != "NA" && $oind_obj[2] != "") {
                            $app_role1 = $oind_obj[2];
                        }

                        $role_object = array();
                        if ($company_key != "" && $company_key != "NA") {
                            $company = $comp_ids[$company_key];
                            if (!in_array($company, $company_id_array)) {
                                $company_id_array[] = $company;
                            }
                            if ($office != "" && $office != "NA") {

                                $query1 = $this->CI->model_all->execute_query("select o.id from offices o,branches b where b.office_id=o.id and b.company='$company' and b.name='$office'");
                                if ($query1->num_rows() > 0) {
                                    $rs1 = $query1->row();
                                    $office_id = $rs1->id;
                                } else {
                                    $this->CI->model_all->execute_query("INSERT INTO `offices` (`id`, `name`) VALUES (NULL, '$office')");
                                    $office_id = $this->CI->db->insert_id();
                                }
                                $query = $this->CI->model_all->execute_query("select id from branches where office_id='$office_id' and company='$company'");
                                if ($query->num_rows() > 0) {
                                    $rs = $query->row();
                                    $branch_id = $rs->id;
                                    if (count($branch_id_array) > 0) {
                                        if (!in_array($branch_id, $branch_id_array)) {
                                            $branch_id_array[] = $branch_id;
                                        }
                                        $role_object["branch"] = $branch_id;
                                    } else {
                                        $branch_id_array[] = $branch_id;
                                        $role_object["branch"] = $branch_id;
                                    }
                                } else {
                                    $this->CI->model_all->execute_query("INSERT INTO `branches` (`id`, `name`,`company`,`office_id`) VALUES (NULL, '$office','$company','$office_id')");
                                    $branch_id = $this->CI->db->insert_id();
                                    $branch_id_array[] = $branch_id;
                                    $role_object["branch"] = $branch_id;
                                }
                            }


                            if ($app_role1 != "" && $app_role1 != "NA") {
                                $app_role_array = explode(",", $app_role1);
                                for ($a = 0; $a < count($app_role_array); $a++) {
                                    $role = trim($app_role_array[$a]);
                                    if ($role != "") {
                                        $query = $this->CI->model_all->execute_query("select  id from app_roles where role_name='$role'");
                                        if ($query->num_rows() > 0) {
                                            $rs = $query->row();
                                            if (!in_array($rs->id, $desg_array)) {
                                                $desg_array[] = $rs->id;
                                                $desg_str = $desg_str . $rs->id . ",";
                                            }
                                            $role_object["role"] = $rs->id;
                                        } else {
                                            $pack_rs = $this->CI->model_all->execute_query("INSERT INTO `app_roles` (`id`, `role_name`, `short_form`, `is_trade`) VALUES (NULL, '$role', 'UN', '0')");
                                            $inserted_id = $this->CI->db->insert_id();
                                            if (!in_array($inserted_id, $desg_array)) {
                                                $desg_array[] = $rs->inserted_id;
                                                $desg_str = $desg_str . $inserted_id . ",";
                                            }

                                            $role_object["role"] = $inserted_id;
                                        }
                                    }
                                }
                            }
                            $emp_roles[] = $role_object;
                        } else {
                            $sub_error_msg = $emp_name . "(" . $emp_code . ")";
                        }
                    } else {
                        $sub_error_msg = $emp_name . "(" . $emp_code . ")";
                    }
                    $error_msg .= $sub_error_msg . ",";
                }
                $error_msg = trim($error_msg, ",");
                $desg_str = trim($desg_str, ",");


                if ($department != "") {
                    $query = $this->CI->model_all->execute_query("select id from departments where name='$department'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $dept_id = $rs->id;
                    } else {
                        $pack_rs = $this->CI->model_all->execute_query("INSERT INTO `departments` (`id`, `name`) VALUES (NULL, '$department')");
                        $dept_id = $this->CI->db->insert_id();
                    }
                }


                if ($country != "") {
                    $query = $this->CI->model_all->execute_query("select id from countries where name='$country'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $country_id = $rs->id;
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `countries` (`id`, `name`) VALUES (NULL, '$country')");
                        $country_id = $this->CI->db->insert_id();
                    }
                }

                if ($state != "") {
                    $query = $this->CI->model_all->execute_query("select id from states where state='$state' and country='$country_id'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $state_id = $rs->id;
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `states` (`id`, `state`,`country`) VALUES (NULL, '$state','$country_id')");
                        $state_id = $this->CI->db->insert_id();
                    }
                }

                if ($district != "") {
                    $query = $this->CI->model_all->execute_query("select id from districts where district='$district' and state='$state_id'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $district_id = $rs->id;
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `districts` (`id`, `district`,`state`) VALUES (NULL, '$district','$state_id')");
                        $district_id = $this->CI->db->insert_id();
                    }
                }



                $dt = date("Y-m-d");
                $dob = str_replace(" ", "", $dob);
                if ($dob != "" && $dob != "NA") {
                    $dob = date("Y-m-d", strtotime($dob));
                }
                $doj = str_replace(" ", "", $doj);
                if ($doj != "" && $doj != "NA") {
                    $doj = date("Y-m-d", strtotime($doj));
                }








                $old_role_array = array();
                $old_branch_array = array();
                $old_company_array = array();
                $old_branch = "";
                $old_company = "";
                $old_role = "";
                $is_update = false;
                if ($emp_code != "" && $emp_code != "NA") {
                    $report_to = $this->CI->model_all->tableFieldData("select id from employees where uniq_id='$report_to_id'", "id");
                    $emp_qry = $this->CI->model_all->execute_query("select * from employees where uniq_id='$emp_code'");
                    if ($emp_qry->num_rows() > 0) {
                        $emp_rs = $emp_qry->row();
                        $employee_id = $emp_rs->id;
                        $old_role = $emp_rs->role_id;
                        $old_role_array = explode(",", $old_role);
                        $old_branch = $emp_rs->branch;
                        $old_branch_array = explode(",", $old_branch);
                        $old_company = $emp_rs->worked_for;
                        $old_company_array = explode(",", trim($old_company));
                        $is_update = true;

                        $main_query = $this->CI->model_all->execute_query("update employees set  `first_name`='$emp_name',`last_name`='',`dept`='$dept_id',`mobile`='$mobile',`email`='$email',`dob`='$dob',`gender`='$gender',`location`='',`blood_group`='$blood_group',`father`='$father',`mother`='$mother',`marital`='$mstatus',`expeience`='$experience',`report_to`='$report_to',`doj`='$doj',`pan`='$pan',`pf`='$pf',`esi`='$esi',`bank_name`='$bank',`bank_account`='$accno',`ifsc`='$ifsc',`address`='',`addressproof1`='',`addressproof2`='',`ofc_email`='$company_mail',`ofc_contact`='$company_contact',`modifiedon`='$dt',`modifiedby`='',`designation`='$designation' where id='$employee_id'");
                    } else {
                        $main_query = "INSERT INTO `employees` (`id`, `first_name`, `last_name`, `uniq_id`, `branch`, `worked_for`, `dept`, `mobile`, `email`, `dob`, `gender`, `location`, `blood_group`, `father`, `mother`, `marital`, `expeience`, `report_to`, `doj`, `pan`, `pf`, `esi`, `bank_name`, `bank_account`, `ifsc`, `address`, `addressproof1`, `addressproof2`, `role_id`, `ofc_email`, `ofc_contact`, `createdon`, `createdby`, `modifiedon`, `modifiedby`, `designation`) VALUES (NULL, '$emp_name', NULL, '$emp_code', '0', '0', '$dept_id','$mobile', '$email','$dob','$gender', '','$blood_group','$father','$mother','$mstatus','$experience','$report_to','$doj','$pan','$pf','$esi','$bank', '$accno', '$ifsc', '$address', '', '', '$desg_str', '$company_mail', '$company_contact', '$dt', '0', '$dt', '0', '$designation')";
                        $this->CI->model_all->execute_query($main_query);
                        $employee_id = $this->CI->db->insert_id();
                    }
                } else {
                    $this->CI->model_all->execute_query($main_query);
                    $employee_id = $this->CI->db->insert_id();
                }


                if ($employee_id > 0) {
                    $password = md5('123456');
                    $prefix_qry = $this->CI->model_all->execute_query("select prefix from companies where company_id='$company'");
                    if ($prefix_qry->num_rows() > 0) {
                        $prefix_rs = $prefix_qry->row();
                        $prefix = $prefix_rs->prefix;
                    }
                    if ($emp_code == "") {
                        $uniq_code = prefix_zeros($employee_id);
                        $uniq_code = $prefix . $uniq_code;
                        $this->CI->model_all->execute_query("update  employees set uniq_id='$uniq_code' where id='$employee_id'");
                    } else {

                        $uniq_code = $emp_code;
                        $this->CI->model_all->execute_query("update  employees set uniq_id='$uniq_code' where id='$employee_id'");
                        // }
                    }

                    if ($cluster != "") {
                        $query = $this->CI->model_all->execute_query("select id from  clusters where name='$cluster'");
                        if ($query->num_rows() > 0) {
                            $rs = $query->row();
                            $cluster_id = $rs->id;
                        } else {
                            $this->CI->model_all->execute_query("INSERT INTO `clusters` (`id`, `name`) VALUES (NULL, '$cluster')");
                            $cluster_id = $this->CI->db->insert_id();
                        }
                        $emp_cluster_count = $this->CI->model_all->tableFieldData("select count(*) as count from `emp_cluster` where `cluster`='$cluster_id' and `employee_id`='$employee_id'", "count");
                        if ($emp_cluster_count > 0) {
                            $this->CI->model_all->execute_query("INSERT INTO `emp_cluster` (`id`, `employee_id`, `cluster`) VALUES(NULL, '$employee_id', '$cluster_id')");
                        }
                    }




                    //print_r($emp_roles);
                    for ($b = 0; $b < count($emp_roles); $b++) {
                        $role_object = $emp_roles[$b];
                        $lbranch = $role_object['branch'];
                        $lrole = $role_object['role'];
                        $emp_role_count = $this->CI->model_all->tableFieldData("select count(*) as count from `emp_roles` where `role_id`='$lrole' and `employee_id`='$employee_id' and `branch_id`='$lbranch'", "count");
                        if ($emp_role_count == 0) {
                            $this->CI->model_all->execute_query("INSERT INTO `emp_roles` (`id`, `employee_id`, `branch_id`, `role_id`) VALUES (NULL, '$employee_id', '$lbranch', '$lrole')");
                        }
                    }



                    if (count($old_role_array) > 0) {
                        for ($or = 0; $or < count($old_role_array); $or++) {
                            if (!in_array($old_role_array[$or], $desg_array)) {
                                $desg_array[] = $old_role_array[$or];
                            }
                        }
                        $desg_str = implode(",", $desg_array);
                    }


                    $branch_id_str = "";
                    if (count($branch_id_array) > 0) {

                        for ($oc = 0; $oc < count($old_branch_array); $oc++) {
                            if (!in_array($old_branch_array[$oc], $branch_id_array)) {
                                $branch_id_array[] = $old_branch_array[$oc];
                            }
                        }
                    } else {
                        for ($oc = 0; $oc < count($old_branch_array); $oc++) {
                            $branch_id_array[] = $old_branch_array[$oc];
                        }
                    }




                    if (count($branch_id_array) > 0)
                        $branch_id_str = implode(",", $branch_id_array);


                    for ($oc = 0; $oc < count($old_company_array); $oc++) {
                        if (!in_array($old_company_array[$oc], $company_id_array)) {
                            $company_id_array[] = $old_company_array[$oc];
                        }
                    }


                    $old_company_str = "";
                    if (count($company_id_array) > 0) {
                        $old_company_str = implode(",", $company_id_array);
                    }


                    $branch_id_str = trim($branch_id_str, ",");
                    $old_company_str = trim($old_company_str, ",");
                    $desg_str = trim($desg_str, ",");
                    //  echo "update  employees set branch='$branch_id_str',worked_for='$old_company_str',role_id='$desg_str' where id='$employee_id'==>$uniq_id<br>";
                    $this->CI->model_all->execute_query("update  employees set branch='$branch_id_str',worked_for='$old_company_str',role_id='$desg_str' where id='$employee_id'");



//

                    $app_users_count = $this->CI->model_all->tableFieldData("select count(*) as count from `app_users` where `role`='trade' and `pkid`='$employee_id'", "count");
                    if ($app_users_count == 0) {
                        $this->CI->model_all->execute_query("INSERT INTO `app_users` (`id`, `dp`, `username`, `password`, `role`, `pkid`, `status`, `fcm_id`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, 'noimage.png', '$emp_code','$password', 'trade', '$employee_id', '1', NULL, '$dt', '1', '$dt', '1')");
                    }





                    if ($address != "" && $address != "NA") {
                        $address_count = $this->CI->model_all->tableFieldData("select count(*) as count from `addresses` where `user_role`='trade' and `user_id`='$employee_id'", "count");
                        if ($address_count == 0) {
                            $this->CI->model_all->execute_query("INSERT INTO `addresses` (`id`, `latitude`, `longitude`, `door_no`, `street_name`, `landmark`, `address`, `city`, `district`, `state`, `country`, `locale`, `pincode`, `is_default`, `user_id`, `user_role`, `status`) VALUES (NULL, NULL, NULL, NULL, NULL, NULL, '$address','$town', '$district_id', '$state_id', '$country_id', NULL, '$pincode', '1', '$employee_id', 'trade', '1')");
                        }
                    }
                }
            }
        }

        if ($error_msg != "") {
            $message = "The Sheet has uploaded with the follwing errors <br> Due to required field(S) missing, " . $error_msg . "details may not worked properly.<br>";
        } else {
            $message = "The Sheet has uploaded successfully";
        }
        return $message;
    }

    function v1_dealer_dump($company, $branch, $row_numbers = false, $col_letters = false, $sheet = 0, $table_class = 'excel') {

        $col_count = $this->rowcount($sheet);
        $error_msg = "";
        $flag = false;
        $rows_excel = $this->rowcount($sheet);

        
        for ($rown = 2; $rown <= $rows_excel; $rown++) {
           // echo $rown . "<br>";
            $dealer_code = "";

            $dealer_code = $this->format_cell_data($this->val($rown, 1, $sheet)); // completed
            $company_name = $this->format_cell_data($this->val($rown, 2, $sheet)); // Completed
            $door_no = $this->format_cell_data($this->val($rown, 3, $sheet)); // Completed
            if ($door_no == "NA")
                $door_no = "";

            $street = $this->format_cell_data($this->val($rown, 4, $sheet)); // Completed
            if ($street == "NA")
                $street = "";

            $landmark = $this->format_cell_data($this->val($rown, 5, $sheet)); // Completed
            if ($landmark == "NA")
                $landmark = "";

            $town = $this->format_cell_data($this->val($rown, 6, $sheet)); // Completed
            if ($town == "NA")
                $town = "";
            $district = $this->format_cell_data($this->val($rown, 7, $sheet)); // Completed
            $state = $this->format_cell_data($this->val($rown, 8, $sheet)); // Completed
            $country = $this->format_cell_data($this->val($rown, 9, $sheet)); // Completed
            $pincode = $this->format_cell_data($this->val($rown, 10, $sheet)); // Completed
            $gstin = $this->format_cell_data($this->val($rown, 11, $sheet)); // Completed
            $pan = $this->format_cell_data($this->val($rown, 12, $sheet)); // Completed



            $sm_codes = $this->format_cell_data($this->val($rown, 13, $sheet)); // completed

            $contact1 = $this->format_cell_data($this->val($rown, 14, $sheet)); // completed
            $dealer_whatsapp = $this->format_cell_data($this->val($rown, 15, $sheet)); // completed
            $contact2 = $this->format_cell_data($this->val($rown, 16, $sheet)); // completed
            $email = $this->format_cell_data($this->val($rown, 17, $sheet)); // completed

            $bank_accno = $this->format_cell_data($this->val($rown, 18, $sheet)); // completed
            $bank_name = $this->format_cell_data($this->val($rown, 19, $sheet)); // completed
            $bank_branch = $this->format_cell_data($this->val($rown, 20, $sheet)); // completed
            $ifsc = $this->format_cell_data($this->val($rown, 21, $sheet)); // completed

            $deposit_amount = $this->format_cell_data($this->val($rown, 22, $sheet));
            $deposit_amount_calc_date = $this->format_cell_data($this->val($rown, 23, $sheet));
            if ($deposit_amount_calc_date != "" && $deposit_amount_calc_date != "NA") {
                $deposit_amount_calc_date = date("Y-m-d", strtotime($deposit_amount_calc_date));
            } else {
                $deposit_amount_calc_date = "";
            }


            $grade = $this->format_cell_data($this->val($rown, 24, $sheet));
            $credit_limit = $this->format_cell_data($this->val($rown, 25, $sheet));
            if ($credit_limit == "") {
                $credit_limit = 250000.00;
            }

            $fertilizer = $this->format_cell_data($this->val($rown, 26, $sheet)); // completed
            $fertilizer_upto = $this->format_cell_data($this->val($rown, 27, $sheet)); // completed
            $pesticide = $this->format_cell_data($this->val($rown, 28, $sheet)); // completed
            $pesticide_upto = $this->format_cell_data($this->val($rown, 29, $sheet)); // completed
            $seed = $this->format_cell_data($this->val($rown, 30, $sheet)); // completed
            $seed_upto = $this->format_cell_data($this->val($rown, 31, $sheet)); // completed
            $other = $this->format_cell_data($this->val($rown, 32, $sheet)); // completed
            $other_upto = $this->format_cell_data($this->val($rown, 33, $sheet)); // completed

            if ($seed_upto != "" && $seed_upto != "NA") {
                $seed_upto = date("Y-m-d H:i:s", strtotime($seed_upto));
            }
            if ($pesticide_upto != "" && $pesticide_upto != "NA") {
                $pesticide_upto = date("Y-m-d H:i:s", strtotime($pesticide_upto));
            }
            if ($fertilizer_upto != "" && $fertilizer_upto != "NA") {
                $fertilizer_upto = date("Y-m-d H:i:s", strtotime($fertilizer_upto));
            }

            if ($other_upto != "" && $other_upto != "NA") {
                $other_upto = date("Y-m-d H:i:s", strtotime($other_upto));
            }



            $dealer_name = $this->format_cell_data($this->val($rown, 34, $sheet)); // completed
            $owner_desg = $this->format_cell_data($this->val($rown, 35, $sheet)); // completed;
            $mobile = $this->format_cell_data($this->val($rown, 36, $sheet)); // completed


            $key_person = $this->format_cell_data($this->val($rown, 37, $sheet)); // completed
            $contact_personal = $this->format_cell_data($this->val($rown, 38, $sheet)); // completed;
            $kp_designation = $this->format_cell_data($this->val($rown, 39, $sheet)); // completed


            $hq = $this->format_cell_data($this->val($rown, 40, $sheet));
            $company_area = $this->format_cell_data($this->val($rown, 41, $sheet));
            $region = $this->format_cell_data($this->val($rown, 42, $sheet));
            $cluster = $this->format_cell_data($this->val($rown, 43, $sheet));
            $reg_type = "Registered";

            $contact_whatsupp = "";









            //  $deposit_date = $this->format_cell_data($this->val($row, 27, $sheet));

















            $address = "";
            if ($door_no != "") {
                $address = $address . "," . $door_no;
            }
            if ($street != "") {
                $address = $address . "," . $street;
            }
            if ($landmark != "") {
                $address = $address . "," . $landmark;
            }
            if ($town != "") {
                $address = $address . "," . $town;
            }
            if ($district != "") {
                $address = $address . "," . $district;
            }
            if ($state != "") {
                $address = $address . "," . $state;
            }
            if ($pincode != "" && $pincode != "NA") {
                $address = $address . "," . $pincode;
            }

            $address = trim($address, ",");










            $dt = date("Y-m-d H:i:s");
            $sm_code_id_array = array();
            if ($sm_codes != "") {
                $sm_code_array = explode(",", $sm_codes);
                for ($i = 0; $i < count($sm_code_array); $i++) {
                    $code = trim($sm_code_array[$i]);

                    $sm = $this->CI->model_all->tableFieldData("select id from employees where uniq_id='$code'", "id");
                    if (!in_array($sm, $sm_code_id_array)) {
                        $sm_code_id_array[] = $sm;
                    }
                }
            }



            if ($country != "") {
                $query = $this->CI->model_all->execute_query("select id from countries where name='$country'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $country_id = $rs->id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `countries` (`id`, `name`) VALUES (NULL, '$country')");
                    $country_id = $this->CI->db->insert_id();
                }
            }

            if ($state != "") {
                $query = $this->CI->model_all->execute_query("select id from states where state='$state' and country='$country_id'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $state_id = $rs->id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `states` (`id`, `state`,`country`) VALUES (NULL, '$state','$country_id')");
                    $state_id = $this->CI->db->insert_id();
                }
            }

            if ($district != "") {
                $query = $this->CI->model_all->execute_query("select id from districts where district='$district' and state='$state_id'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $district_id = $rs->id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `districts` (`id`, `district`,`state`) VALUES (NULL, '$district','$state_id')");
                    $district_id = $this->CI->db->insert_id();
                }
            }

            if ($company_area != "") {
                $query = $this->CI->model_all->execute_query("select carea_id from company_areas where  carea_name='$company_area'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $carea_id = $rs->carea_id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `company_areas` (`carea_id`, `carea_name`) VALUES (NULL, '$company_area')");
                    $carea_id = $this->CI->db->insert_id();
                }
            }

            if ($cluster != "") {
                $query = $this->CI->model_all->execute_query("select id from clusters where  name='$cluster'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $cluster_id = $rs->id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `clusters` (`id`, `name`) VALUES (NULL, '$cluster')");
                    $cluster_id = $this->CI->db->insert_id();
                }
            }

            if ($region != "") {
                $query = $this->CI->model_all->execute_query("select id from company_regions where  name='$region'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $region_id = $rs->id;
                } else {
                    $this->CI->model_all->execute_query("INSERT INTO `company_regions` (`id`, `name`) VALUES (NULL, '$region')");
                    $region_id = $this->CI->db->insert_id();
                }
            }









            if ($dealer_name == "") {
                $dealer_name = $key_person;
            }


            $dealer_id = 0;
            $pre_company = "";
            $pre_branch = "";
            $pre_sm = "";

            if ($company_name != "") {

                $dealer_qry = $this->CI->model_all->execute_query("select * from sellers where dealer_code='$dealer_code'");
                if ($dealer_qry->num_rows() > 0) {
                    $dealer_rs = $dealer_qry->row();
                    $dealer_id = $dealer_rs->id;
                    $pre_company = $dealer_rs->dealer_for;
                    $pre_branch = $dealer_rs->branch;
                    $pre_sm = $dealer_rs->sales_manager;
                }
                $pre_company_array = explode(",", $pre_company);
                $pre_branch_array = explode(",", $pre_branch);
                $pre_sm_array = explode(",", $pre_sm);
                if (!in_array($company, $pre_company_array))
                    $pre_company_array[] = $company;
                if (!in_array($branch, $pre_branch_array))
                    $pre_branch_array[] = $branch;
                for ($k = 0; $k < count($sm_code_id_array); $k++) {
                    if (!in_array($sm_code_id_array[$k], $pre_sm_array))
                        $pre_sm_array[] = $sm_code_id_array[$k];
                }


                if ($dealer_id > 0) {
                    //echo "update `sellers` set `first_name`='$dealer_name', `last_name`='', `dealer_code`='$dealer_code', `contact1`='$contact1', `contact2`='$contact2',`whatsapp`='$dealer_whatsapp', `email`='$email', `mobile`='$mobile', `owner_desg`='$owner_desg', `latitude`='', `longitude`='', `address`='', `company_name`='$company_name', `gstin`='$gstin', `pan`='$pan', `division`='', `bank_name`='$bank_name', `bank_accno`='$bank_accno', `bank_branch`='$bank_branch', `ifsc`='$ifsc', `reg_type`='$reg_type', `addressproof1`='', `addressproof2`='',  `modifiedby`='15', `modifiedon`='$dt', `status`='1',  `credit_limit`='$credit_limit',`hq`='$hq' where id='$dealer_id'";
                    $affected_rows = $this->CI->model_all->execute_query("update `sellers` set `first_name`='$dealer_name', `last_name`='', `dealer_code`='$dealer_code', `contact1`='$contact1', `contact2`='$contact2',`whatsapp`='$dealer_whatsapp', `email`='$email', `mobile`='$mobile', `owner_desg`='$owner_desg', `latitude`='', `longitude`='', `address`='', `company_name`='$company_name', `gstin`='$gstin', `pan`='$pan', `division`='', `bank_name`='$bank_name', `bank_accno`='$bank_accno', `bank_branch`='$bank_branch', `ifsc`='$ifsc', `reg_type`='$reg_type', `addressproof1`='', `addressproof2`='',  `modifiedby`='15', `modifiedon`='$dt', `status`='1',  `credit_limit`='$credit_limit',`hq`='$hq' where id='$dealer_id'");
                    if ($affected_rows) {
                        $flag = true;
                    }
                    $this->CI->model_all->execute_query("update `addresses` set `latitude`='', `longitude`='', `door_no`='$door_no', `street_name`='$street', `landmark`='$landmark', `address`='$address', `city`='$town', `district`='$district_id', `state`='$state_id', `country`='$country_id', `locale`='', `pincode`='$pincode' where `is_default`='1' and  `user_id`='$dealer_id' and `user_role`='DEALER'");
                } else {
                    $password = md5('123456');
                   // echo "INSERT INTO `sellers` (`id`, `first_name`, `last_name`, `dealer_code`, `contact1`, `contact2`, `whatsapp`, `email`, `mobile`, `owner_desg`, `latitude`, `longitude`, `address`, `company_name`, `gstin`, `pan`, `division`, `bank_name`, `bank_accno`, `bank_branch`, `ifsc`, `reg_type`, `addressproof1`, `addressproof2`, `createdby`, `createdon`, `modifiedby`, `modifiedon`, `status`, `branch`, `pickerid`, `dealer_for`, `sales_manager`, `am_contact`, `credit_limit`,`hq`)  VALUES (NULL, '$dealer_name', NULL, '$dealer_code', '$contact1', '$contact2', '$dealer_whatsapp', '$email', '$mobile', '$owner_desg', NULL, NULL, '', '$company_name', '$gstin', '$pan', NULL, '$bank_name', '$bank_accno', '$bank_branch', '$ifsc', '$reg_type', '', '', '1', '$dt', '1', '$dt', '1', '$branch', '', '$company', '$sm', '', '$credit_limit','$hq')";
                    $this->CI->model_all->execute_query("INSERT INTO `sellers` (`id`, `first_name`, `last_name`, `dealer_code`, `contact1`, `contact2`, `whatsapp`, `email`, `mobile`, `owner_desg`, `latitude`, `longitude`, `address`, `company_name`, `gstin`, `pan`, `division`, `bank_name`, `bank_accno`, `bank_branch`, `ifsc`, `reg_type`, `addressproof1`, `addressproof2`, `createdby`, `createdon`, `modifiedby`, `modifiedon`, `status`, `branch`, `pickerid`, `dealer_for`, `sales_manager`, `am_contact`, `credit_limit`,`hq`)  VALUES (NULL, '$dealer_name', NULL, '$dealer_code', '$contact1', '$contact2', '$dealer_whatsapp', '$email', '$mobile', '$owner_desg', NULL, NULL, '', '$company_name', '$gstin', '$pan', NULL, '$bank_name', '$bank_accno', '$bank_branch', '$ifsc', '$reg_type', '', '', '1', '$dt', '1', '$dt', '1', '$branch', '', '$company', '$sm', '', '$credit_limit','$hq')");
                    $dealer_id = $this->CI->db->insert_id();
					if($deposit_amount!="" && $deposit_amount_calc_date!=""){
	

                      $this->CI->model_all->execute_query("INSERT INTO `closing_balance` (`id`, `dealer_id`, `closing_balance`, `closing_date`) VALUES (NULL, '$dealer_id', '$deposit_amount', '$deposit_amount_calc_date')");
                    }
                    //http://www.ammyy.com/en/  INSERT INTO `closing_balance` (`id`, `dealer_id`, `closing_balance`, `closing_date`) VALUES (NULL, '', '', '')
                    if ($dealer_id > 0) {
                        $flag = true;
                    }
                    if ($dealer_code == "") {
                        $dealer_code = prefix_zeros($dealer_id);
                        $dealer_code = "NAD" . $dealer_code;
                        $this->CI->model_all->execute_query("update sellers set dealer_code='$dealer_code' where id='$dealer_id'");
                    }
                    $this->CI->model_all->execute_query("INSERT INTO `app_users` (`id`, `dp`, `username`, `password`, `role`, `pkid`, `status`, `fcm_id`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, 'noimage.png', '$dealer_code','$password', 'seller', '$dealer_id', '1', NULL, '$dt', '1', '$dt', '1')");
                    $this->CI->model_all->execute_query("INSERT INTO `addresses` (`id`, `latitude`, `longitude`, `door_no`, `street_name`, `landmark`, `address`, `city`, `district`, `state`, `country`, `locale`, `pincode`, `is_default`, `user_id`, `user_role`, `status`) VALUES (NULL, NULL, NULL, '$door_no', '$street', '$landmark', '$address','$town', '$district_id', '$state_id', '$country_id', NULL, '$pincode', '1', '$dealer_id', 'DEALER', '1')");
                }



                if ($dealer_id > 0) {



                    $ferti_qry = $this->CI->model_all->execute_query("select * from delaer_licenses where seller_id='$dealer_id'");
                    if ($ferti_qry->num_rows() > 0) {
                        $ferti_rs = $ferti_qry->row();
                        $this->CI->model_all->execute_query("update `delaer_licenses`   `fertilizer`='$fertilizer', `fertilizer_upto`='$fertilizer_upto', `pesticide`='$pesticide', `pesticide_upto`='$pesticide_upto', `seed`='$seed', `seed_upto`='$seed_upto', `other`='$other',`other_upto`='$other_upto' where id='$ferti_rs->id'");
                    } else {
                        if ($fertilizer != "" || $fertilizer_upto != "" || $seed != "" || $seed_upto != "" || $pesticide != "" || $pesticide_upto != "" || $other != "" || $other_upto != "")
                            $this->CI->model_all->execute_query("INSERT INTO `delaer_licenses` (`id`, `seller_id`, `fertilizer`, `fertilizer_upto`, `pesticide`, `pesticide_upto`, `seed`, `seed_upto`, `other`,`other_upto`) VALUES (NULL, '$dealer_id', '$fertilizer', '$fertilizer_upto', '$pesticide', '$pesticide_upto', '$seed', '$seed_upto', '$other','$other_upto')");
                    }


                    $req_qry = $this->CI->model_all->execute_query("select * from branch_dealers where branch='$branch' and seller='$dealer_id'");
                    if ($req_qry->num_rows() > 0) {
                        if (count($sm_code_id_array) > 0) {
                            for ($i = 0; $i < count($sm_code_id_array); $i++) {
                                $detail_qry = $this->CI->model_all->execute_query("select * from branch_dealers where `branch`='$branch' and  `seller`='$dealer_id' and `sales_manager`='$sm_code_id_array[$i]'");
                                if ($detail_qry->num_rows() > 0) {
                                    $detail_rs = $detail_qry->row();
                                    $this->CI->model_all->execute_query("update `branch_dealers` set  `area`='$carea_id', `region`='$region_id', `cluster`='$cluster_id' where branch='$branch' and seller='$dealer_id' and `sales_manager`='$sm_code_id_array[$i]'");
                                } else {
                                    $this->CI->model_all->execute_query("INSERT INTO `branch_dealers` (`id`, `branch`, `seller`, `sales_manager`, `area`, `region`, `cluster`) VALUES (NULL, '$branch', '$dealer_id', '$sm_code_id_array[$i]', '$carea_id', '$region_id', '$cluster_id')");
                                }
                            }
                        }
                    } else {
                        if (count($sm_code_id_array) > 0) {
                            for ($i = 0; $i < count($sm_code_id_array); $i++) {
                                $this->CI->model_all->execute_query("INSERT INTO `branch_dealers` (`id`, `branch`, `seller`, `sales_manager`, `area`, `region`, `cluster`) VALUES (NULL, '$branch', '$dealer_id', '$sm_code_id_array[$i]', '$carea_id', '$region_id', '$cluster_id')");
                            }
                        }
                    }

                    // echo "INSERT INTO `app_users` (`id`, `dp`, `username`, `password`, `role`, `pkid`, `status`, `fcm_id`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, 'noimage.png', '$dealer_code','$password', 'seller', '$dealer_id', '1', NULL, '$dt', '1', '$dt', '1')\n";

                    $req_qry = $this->CI->model_all->execute_query("select * from contacts where role_id='$dealer_id'  and role='DEALER'");
                    if ($req_qry->num_rows() > 0) {
                        $req_rs = $req_qry->row();
                     //   echo "update `contacts` set `name`='$key_person', `contact_personal`='$contact_personal', `contact_watsup`='$contact_whatsupp', `contact_fb`='', `email`='', `designation`='$kp_designation' where `id`='$req_rs->id'";
                        $this->CI->model_all->execute_query("update `contacts` set `name`='$key_person', `contact_personal`='$contact_personal', `contact_watsup`='$contact_whatsupp', `contact_fb`='', `email`='', `designation`='$kp_designation' where `id`='$req_rs->id'");
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `contacts` (`id`, `name`, `contact_personal`, `contact_watsup`, `contact_fb`, `email`, `designation`, `role`, `role_id`) VALUES (NULL, '$key_person', '$contact_personal', '$contact_whatsupp', NULL, NULL, '$kp_designation', 'DEALER', '$dealer_id')");
                    }

                    $final_branch = '';
                    $final_company = '';
                    $final_sm = '';
                    if (count($pre_company_array) > 0)
                        $final_company = implode(",", $pre_company_array);
                    if (count($pre_branch_array) > 0)
                        $final_branch = implode(",", $pre_branch_array);
                    if (count($pre_sm_array) > 0)
                        $final_sm = implode(",", $pre_sm_array);
                    $final_company = trim($final_company,",");
                    $final_branch = trim($final_branch,",");
                    $final_sm = trim($final_sm,",");

                    $this->CI->model_all->execute_query("update sellers set branch='$final_branch',dealer_for='$final_company',sales_manager='$final_sm' where id='$dealer_id'");


                    //echo "End";
                }
            }
        }
        if ($flag) {
            $message = "Dealers Data Uploaded Successfully.";
        } else {
            $message = "Something went wrong. Please try later";
        }
        return $message;
    }

    function v1_items_upload($company, $row_numbers = false, $col_letters = false, $sheet = 0, $table_class = 'excel') {
        $col_count = $this->rowcount($sheet);
        $flag = false;
        for ($row = 2; $row <= $this->rowcount($sheet); $row++) {



            $item_code = $this->format_cell_data($this->val($row, 1, $sheet));
            $barcode = $this->format_cell_data($this->val($row, 2, $sheet));
            $brand = $this->format_cell_data($this->val($row, 3, $sheet));
            $item_name = $this->format_cell_data($this->val($row, 4, $sheet));
            $item_descr = $this->format_cell_data($this->val($row, 5, $sheet));
            $unit_size = $this->format_cell_data($this->val($row, 6, $sheet));
            $pack_type = $this->format_cell_data($this->val($row, 7, $sheet));
            $pack_qty = $this->format_cell_data($this->val($row, 8, $sheet));
            $hsn_code = $this->format_cell_data($this->val($row, 9, $sheet));

            $catg = $this->format_cell_data($this->val($row, 10, $sheet));
            $sub_catg = $this->format_cell_data($this->val($row, 11, $sheet));

            $manufacture_date = $this->format_cell_data($this->val($row, 12, $sheet));
            if ($manufacture_date != "" && $manufacture_date != "NA") {
                $manufacture_date = date("Y-m-d", strtotime($manufacture_date));
            }

            $expiry_date = $this->format_cell_data($this->val($row, 13, $sheet));
            if ($expiry_date != "" && $expiry_date != "NA") {
                $expiry_date = date("Y-m-d", strtotime($expiry_date));
            }
            $batch_no = $this->format_cell_data($this->val($row, 14, $sheet));
            $cgst = $this->format_cell_data($this->val($row, 15, $sheet));
            $sgst = $this->format_cell_data($this->val($row, 16, $sheet));
            $igst = $this->format_cell_data($this->val($row, 17, $sheet));
            $basic_price = $this->format_cell_data($this->val($row, 18, $sheet)); // mrp
            $discount_price = $this->format_cell_data($this->val($row, 19, $sheet)); // 
            $net_price = $this->format_cell_data($this->val($row, 20, $sheet)); // dealer_price
            $mrp = $this->format_cell_data($this->val($row, 21, $sheet)); // company_mrp

            $available_qty = $this->format_cell_data($this->val($row, 22, $sheet));
            $product_img = $this->format_cell_data($this->val($row, 23, $sheet));
            $product_img1 = $this->format_cell_data($this->val($row, 24, $sheet));
            $product_img2 = $this->format_cell_data($this->val($row, 25, $sheet));


            $dealer_price = $net_price;
            $freeitem = "";







            if ($brand != "") {

                $query = $this->CI->model_all->execute_query("select brand_id from brands where brand='$brand' and company_id='$company'");
                if ($query->num_rows() > 0) {
                    $rs = $query->row();
                    $brand_id = $rs->brand_id;
                } else {
                    $brand_rs = $this->CI->model_all->execute_query("INSERT INTO `brands` (`brand_id`, `brand`, `company_id`) VALUES (NULL, '$brand', '$company')");
                    $brand_id = $this->CI->db->insert_id();
                }

                if ($unit_size != "") {
                    $query = $this->CI->model_all->execute_query("select unit_id from unit_sizes where unit_name='$unit_size'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $unit_id = $rs->unit_id;
                    } else {
                        $pack_rs = $this->CI->model_all->execute_query("INSERT INTO `unit_sizes` (`unit_id`, `unit_name`) VALUES (NULL, '$unit_size')");
                        $unit_id = $this->CI->db->insert_id();
                    }
                }

                if ($pack_type != "") {
                    $query = $this->CI->model_all->execute_query("select id from pack_types where name='$pack_type'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $pack_id = $rs->id;
                    } else {
                        $pack_rs = $this->CI->model_all->execute_query("INSERT INTO `pack_types` (`id`, `name`) VALUES (NULL, '$pack_type')");
                        $pack_id = $this->CI->db->insert_id();
                    }
                }

                if ($catg != "") {
                    $query = $this->CI->model_all->execute_query("select id from categories where categoryname='$catg'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $catg_id = $rs->id;
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `categories` (`id`, `categoryname`, `parentid`, `icon`, `status`,`company`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$catg', '0', '', '1','$company', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')");
                        $catg_id = $this->CI->db->insert_id();
                    }
                }

                //  echo "INSERT INTO `categories` (`id`, `categoryname`, `parentid`, `icon`, `status`,`company`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$catg', '0', '', '1','$company', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')";
                if ($sub_catg != "") {
                    $query = $this->CI->model_all->execute_query("select id from categories where categoryname='$sub_catg'");
                    if ($query->num_rows() > 0) {
                        $rs = $query->row();
                        $sub_catg_id = $rs->id;
                    } else {
                        $this->CI->model_all->execute_query("INSERT INTO `categories` (`id`, `categoryname`, `parentid`, `icon`, `status`, `company`,`createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$sub_catg', '$catg_id', '', '1','$company', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')");
                        $sub_catg_id = $this->CI->db->insert_id();
                    }
                }else{
                     $sub_catg_id = $catg_id;
                
                }

                //$item_name = $brand . " " . $pack_type;
                $rq_qry = $this->CI->model_all->execute_query("select id from items where brand='$brand_id' and itemname='$item_name'");
                if ($rq_qry->num_rows() > 0) {
                    $rq_rs = $rq_qry->row();
                    $item_id = $rq_rs->id;
                } else {
                    // echo "INSERT INTO `items` (`id`, `brand`, `itemname`,`item_descr`, `productid`, `unit_size`, `pack_type`,`piece_price`,`piece_dealer_price`, `status`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$brand_id', '$item_name','$item_descr', '$sub_catg_id', '1', '1','$mrp','$dealer_price', '1', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')";
                    $this->CI->model_all->execute_query("INSERT INTO `items` (`id`, `brand`, `itemname`, `productid`, `unit_size`, `pack_type`,`piece_price`,`piece_dealer_price`, `status`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$brand_id', '$item_name', '$sub_catg_id', '1', '1','$mrp','$dealer_price', '1', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')");
                    $item_id = $this->CI->db->insert_id();
                    if ($item_id > 0) {
                        $flag = true;
                    }
                }
                if ($item_id > 0) {
                    $this->CI->model_all->execute_query("INSERT INTO `item_prices` (`id`, `pack_id`,`unit_id`, `pack_qty`, `item_id`, `item_descr`,`batch_no`, `company_mrp`,`mrp`, `dealer_price`, `freeitem`, `barcode`, `sgst`, `cgst`, `igst`, `hsn_code`,`mfg_date`,`exp_date`, `createdon`, `createdby`, `modifiedon`, `modifiedby`) VALUES (NULL, '$pack_id','$unit_id', '$pack_qty', '$item_id', '$item_descr','$batch_no', '$mrp','$basic_price', '$net_price', '$freeitem', '$barcode', '$sgst', '$cgst', '$igst', '$hsn_code','$manufacture_date','$expiry_date', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP, '1')");
                    $flag = true;
                }
                if ($product_img != "") {
                    $this->CI->model_all->execute_query("INSERT INTO `item_images` (`id`, `item`, `img_name`, `status`) VALUES (NULL, '$item_id', '" . $company . "_" . $product_img . ".png', '1')");
                }
                if ($product_img1 != "") {
                    $this->CI->model_all->execute_query("INSERT INTO `item_images` (`id`, `item`, `img_name`, `status`) VALUES (NULL, '$item_id', '" . $company . "_" . $product_img1 . ".png', '1')");
                }
                if ($product_img2 != "") {
                    $this->CI->model_all->execute_query("INSERT INTO `item_images` (`id`, `item`, `img_name`, `status`) VALUES (NULL, '$item_id', '" . $company . "_" . $product_img2 . ".png', '1')");
                }
            }
        }
        

        if ($flag) {
            $message = "Items Data Uploaded Successfully.";
        } else {
            $message = "Something went wrong. Please try later";
        }
        return $message;
    }

// --------------
    // END PUBLIC API


    var $boundsheets = array();
    var $formatRecords = array();
    var $fontRecords = array();
    var $xfRecords = array();
    var $colInfo = array();
    var $rowInfo = array();
    var $sst = array();
    var $sheets = array();
    var $data;
    var $_ole;
    var $_defaultEncoding = "UTF-8";
    var $_defaultFormat = SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT;
    var $_columnsFormat = array();
    var $_rowoffset = 1;
    var $_coloffset = 1;

    /**
     * List of default date formats used by Excel
     */
    var $dateFormats = array(
        0xe => "m/d/Y",
        0xf => "M-d-Y",
        0x10 => "d-M",
        0x11 => "M-Y",
        0x12 => "h:i a",
        0x13 => "h:i:s a",
        0x14 => "H:i",
        0x15 => "H:i:s",
        0x16 => "d/m/Y H:i",
        0x2d => "i:s",
        0x2e => "H:i:s",
        0x2f => "i:s.S"
    );

    /**
     * Default number formats used by Excel
     */
    var $numberFormats = array(
        0x1 => "0",
        0x2 => "0.00",
        0x3 => "#,##0",
        0x4 => "#,##0.00",
        0x5 => "\$#,##0;(\$#,##0)",
        0x6 => "\$#,##0;[Red](\$#,##0)",
        0x7 => "\$#,##0.00;(\$#,##0.00)",
        0x8 => "\$#,##0.00;[Red](\$#,##0.00)",
        0x9 => "0%",
        0xa => "0.00%",
        0xb => "0.00E+00",
        0x25 => "#,##0;(#,##0)",
        0x26 => "#,##0;[Red](#,##0)",
        0x27 => "#,##0.00;(#,##0.00)",
        0x28 => "#,##0.00;[Red](#,##0.00)",
        0x29 => "#,##0;(#,##0)", // Not exactly
        0x2a => "\$#,##0;(\$#,##0)", // Not exactly
        0x2b => "#,##0.00;(#,##0.00)", // Not exactly
        0x2c => "\$#,##0.00;(\$#,##0.00)", // Not exactly
        0x30 => "##0.0E+0"
    );
    var $colors = Array(
        0x00 => "#000000",
        0x01 => "#FFFFFF",
        0x02 => "#FF0000",
        0x03 => "#00FF00",
        0x04 => "#0000FF",
        0x05 => "#FFFF00",
        0x06 => "#FF00FF",
        0x07 => "#00FFFF",
        0x08 => "#000000",
        0x09 => "#FFFFFF",
        0x0A => "#FF0000",
        0x0B => "#00FF00",
        0x0C => "#0000FF",
        0x0D => "#FFFF00",
        0x0E => "#FF00FF",
        0x0F => "#00FFFF",
        0x10 => "#800000",
        0x11 => "#008000",
        0x12 => "#000080",
        0x13 => "#808000",
        0x14 => "#800080",
        0x15 => "#008080",
        0x16 => "#C0C0C0",
        0x17 => "#808080",
        0x18 => "#9999FF",
        0x19 => "#993366",
        0x1A => "#FFFFCC",
        0x1B => "#CCFFFF",
        0x1C => "#660066",
        0x1D => "#FF8080",
        0x1E => "#0066CC",
        0x1F => "#CCCCFF",
        0x20 => "#000080",
        0x21 => "#FF00FF",
        0x22 => "#FFFF00",
        0x23 => "#00FFFF",
        0x24 => "#800080",
        0x25 => "#800000",
        0x26 => "#008080",
        0x27 => "#0000FF",
        0x28 => "#00CCFF",
        0x29 => "#CCFFFF",
        0x2A => "#CCFFCC",
        0x2B => "#FFFF99",
        0x2C => "#99CCFF",
        0x2D => "#FF99CC",
        0x2E => "#CC99FF",
        0x2F => "#FFCC99",
        0x30 => "#3366FF",
        0x31 => "#33CCCC",
        0x32 => "#99CC00",
        0x33 => "#FFCC00",
        0x34 => "#FF9900",
        0x35 => "#FF6600",
        0x36 => "#666699",
        0x37 => "#969696",
        0x38 => "#003366",
        0x39 => "#339966",
        0x3A => "#003300",
        0x3B => "#333300",
        0x3C => "#993300",
        0x3D => "#993366",
        0x3E => "#333399",
        0x3F => "#333333",
        0x40 => "#000000",
        0x41 => "#FFFFFF",
        0x43 => "#000000",
        0x4D => "#000000",
        0x4E => "#FFFFFF",
        0x4F => "#000000",
        0x50 => "#FFFFFF",
        0x51 => "#000000",
        0x7FFF => "#000000"
    );
    var $lineStyles = array(
        0x00 => "",
        0x01 => "Thin",
        0x02 => "Medium",
        0x03 => "Dashed",
        0x04 => "Dotted",
        0x05 => "Thick",
        0x06 => "Double",
        0x07 => "Hair",
        0x08 => "Medium dashed",
        0x09 => "Thin dash-dotted",
        0x0A => "Medium dash-dotted",
        0x0B => "Thin dash-dot-dotted",
        0x0C => "Medium dash-dot-dotted",
        0x0D => "Slanted medium dash-dotted"
    );
    var $lineStylesCss = array(
        "Thin" => "1px solid",
        "Medium" => "2px solid",
        "Dashed" => "1px dashed",
        "Dotted" => "1px dotted",
        "Thick" => "3px solid",
        "Double" => "double",
        "Hair" => "1px solid",
        "Medium dashed" => "2px dashed",
        "Thin dash-dotted" => "1px dashed",
        "Medium dash-dotted" => "2px dashed",
        "Thin dash-dot-dotted" => "1px dashed",
        "Medium dash-dot-dotted" => "2px dashed",
        "Slanted medium dash-dotte" => "2px dashed"
    );

    function read16bitstring($data, $start) {
        $len = 0;
        while (ord($data[$start + $len]) + ord($data[$start + $len + 1]) > 0)
            $len++;
        return substr($data, $start, $len);
    }

    // ADDED by Matt Kruse for better formatting
    function _format_value($format, $num, $f) {
        // 49==TEXT format
        // http://code.google.com/p/php-excel-reader/issues/detail?id=7
        if ((!$f && $format == "%s") || ($f == 49) || ($format == "GENERAL")) {
            return array('string' => $num, 'formatColor' => null);
        }

        // Custom pattern can be POSITIVE;NEGATIVE;ZERO
        // The "text" option as 4th parameter is not handled
        $parts = explode(";", $format);
        $pattern = $parts[0];
        // Negative pattern
        if (count($parts) > 2 && $num == 0) {
            $pattern = $parts[2];
        }
        // Zero pattern
        if (count($parts) > 1 && $num < 0) {
            $pattern = $parts[1];
            $num = abs($num);
        }

        $color = "";
        $matches = array();
        $color_regex = "/^\[(BLACK|BLUE|CYAN|GREEN|MAGENTA|RED|WHITE|YELLOW)\]/i";
        if (preg_match($color_regex, $pattern, $matches)) {
            $color = strtolower($matches[1]);
            $pattern = preg_replace($color_regex, "", $pattern);
        }

        // In Excel formats, "_" is used to add spacing, which we can't do in HTML
        $pattern = preg_replace("/_./", "", $pattern);

        // Some non-number characters are escaped with \, which we don't need
        $pattern = preg_replace("/\\\/", "", $pattern);

        // Some non-number strings are quoted, so we'll get rid of the quotes
        $pattern = preg_replace("/\"/", "", $pattern);

        // TEMPORARY - Convert # to 0
        $pattern = preg_replace("/\#/", "0", $pattern);

        // Find out if we need comma formatting
        $has_commas = preg_match("/,/", $pattern);
        if ($has_commas) {
            $pattern = preg_replace("/,/", "", $pattern);
        }

        // Handle Percentages
        if (preg_match("/\d(\%)([^\%]|$)/", $pattern, $matches)) {
            $num = $num * 100;
            $pattern = preg_replace("/(\d)(\%)([^\%]|$)/", "$1%$3", $pattern);
        }

        // Handle the number itself
        $number_regex = "/(\d+)(\.?)(\d*)/";
        if (preg_match($number_regex, $pattern, $matches)) {
            $left = $matches[1];
            $dec = $matches[2];
            $right = $matches[3];
            if ($has_commas) {
                $formatted = number_format($num, strlen($right));
            } else {
                $sprintf_pattern = "%1." . strlen($right) . "f";
                $formatted = sprintf($sprintf_pattern, $num);
            }
            $pattern = preg_replace($number_regex, $formatted, $pattern);
        }

        return array(
            'string' => $pattern,
            'formatColor' => $color
        );
    }

    /**
     * Constructor
     *
     * Some basic initialisation
     */
    function Spreadsheet_Excel_Reader($file = '', $store_extended_info = true, $outputEncoding = '') {
        $this->_ole = new Oleread();

        $this->CI = & get_instance();
        $this->CI->load->model("model_all");
        $this->setUTFEncoder('iconv');
        if ($outputEncoding != '') {
            $this->setOutputEncoding($outputEncoding);
        }
        for ($i = 1; $i < 245; $i++) {
            $name = strtolower(( (($i - 1) / 26 >= 1) ? chr(($i - 1) / 26 + 64) : '') . chr(($i - 1) % 26 + 65));
            $this->colnames[$name] = $i;
            $this->colindexes[$i] = $name;
        }
        $this->store_extended_info = $store_extended_info;
        if ($file != "") {
            $this->read($file);
        }
    }

    /**
     * Set the encoding method
     */
    function setOutputEncoding($encoding) {
        $this->_defaultEncoding = $encoding;
    }

    /**
     *  $encoder = 'iconv' or 'mb'
     *  set iconv if you would like use 'iconv' for encode UTF-16LE to your encoding
     *  set mb if you would like use 'mb_convert_encoding' for encode UTF-16LE to your encoding
     */
    function setUTFEncoder($encoder = 'iconv') {
        $this->_encoderFunction = '';
        if ($encoder == 'iconv') {
            $this->_encoderFunction = function_exists('iconv') ? 'iconv' : '';
        } elseif ($encoder == 'mb') {
            $this->_encoderFunction = function_exists('mb_convert_encoding') ? 'mb_convert_encoding' : '';
        }
    }

    function setRowColOffset($iOffset) {
        $this->_rowoffset = $iOffset;
        $this->_coloffset = $iOffset;
    }

    /**
     * Set the default number format
     */
    function setDefaultFormat($sFormat) {
        $this->_defaultFormat = $sFormat;
    }

    /**
     * Force a column to use a certain format
     */
    function setColumnFormat($column, $sFormat) {
        $this->_columnsFormat[$column] = $sFormat;
    }

    /**
     * Read the spreadsheet file using OLE, then parse
     */
    function read($sFileName) {
        $res = $this->_ole->read($sFileName);

        // oops, something goes wrong (Darko Miljanovic)
        if ($res === false) {
            // check error code
            if ($this->_ole->error == 1) {
                // bad file
                die('The filename ' . $sFileName . ' is not readable');
            }
            // check other error codes here (eg bad fileformat, etc...)
        }
        $this->data = $this->_ole->getWorkBook();
        $this->_parse();
    }

    /**
     * Parse a workbook
     *
     * @access private
     * @return bool
     */
    function _parse() {
        $pos = 0;
        $data = $this->data;

        $code = v($data, $pos);
        $length = v($data, $pos + 2);
        $version = v($data, $pos + 4);
        $substreamType = v($data, $pos + 6);

        $this->version = $version;

        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) &&
                ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return false;
        }

        if ($substreamType != SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS) {
            return false;
        }

        $pos += $length + 4;

        $code = v($data, $pos);
        $length = v($data, $pos + 2);

        while ($code != SPREADSHEET_EXCEL_READER_TYPE_EOF) {
            switch ($code) {
                case SPREADSHEET_EXCEL_READER_TYPE_SST:
                    $spos = $pos + 4;
                    $limitpos = $spos + $length;
                    $uniqueStrings = $this->_GetInt4d($data, $spos + 4);
                    $spos += 8;
                    for ($i = 0; $i < $uniqueStrings; $i++) {
                        // Read in the number of characters
                        if ($spos == $limitpos) {
                            $opcode = v($data, $spos);
                            $conlength = v($data, $spos + 2);
                            if ($opcode != 0x3c) {
                                return -1;
                            }
                            $spos += 4;
                            $limitpos = $spos + $conlength;
                        }
                        $numChars = ord($data[$spos]) | (ord($data[$spos + 1]) << 8);
                        $spos += 2;
                        $optionFlags = ord($data[$spos]);
                        $spos++;
                        $asciiEncoding = (($optionFlags & 0x01) == 0);
                        $extendedString = ( ($optionFlags & 0x04) != 0);

                        // See if string contains formatting information
                        $richString = ( ($optionFlags & 0x08) != 0);

                        if ($richString) {
                            // Read in the crun
                            $formattingRuns = v($data, $spos);
                            $spos += 2;
                        }

                        if ($extendedString) {
                            // Read in cchExtRst
                            $extendedRunLength = $this->_GetInt4d($data, $spos);
                            $spos += 4;
                        }

                        $len = ($asciiEncoding) ? $numChars : $numChars * 2;
                        if ($spos + $len < $limitpos) {
                            $retstr = substr($data, $spos, $len);
                            $spos += $len;
                        } else {
                            // found countinue
                            $retstr = substr($data, $spos, $limitpos - $spos);
                            $bytesRead = $limitpos - $spos;
                            $charsLeft = $numChars - (($asciiEncoding) ? $bytesRead : ($bytesRead / 2));
                            $spos = $limitpos;

                            while ($charsLeft > 0) {
                                $opcode = v($data, $spos);
                                $conlength = v($data, $spos + 2);
                                if ($opcode != 0x3c) {
                                    return -1;
                                }
                                $spos += 4;
                                $limitpos = $spos + $conlength;
                                $option = ord($data[$spos]);
                                $spos += 1;
                                if ($asciiEncoding && ($option == 0)) {
                                    $len = min($charsLeft, $limitpos - $spos); // min($charsLeft, $conlength);
                                    $retstr .= substr($data, $spos, $len);
                                    $charsLeft -= $len;
                                    $asciiEncoding = true;
                                } elseif (!$asciiEncoding && ($option != 0)) {
                                    $len = min($charsLeft * 2, $limitpos - $spos); // min($charsLeft, $conlength);
                                    $retstr .= substr($data, $spos, $len);
                                    $charsLeft -= $len / 2;
                                    $asciiEncoding = false;
                                } elseif (!$asciiEncoding && ($option == 0)) {
                                    // Bummer - the string starts off as Unicode, but after the
                                    // continuation it is in straightforward ASCII encoding
                                    $len = min($charsLeft, $limitpos - $spos); // min($charsLeft, $conlength);
                                    for ($j = 0; $j < $len; $j++) {
                                        $retstr .= $data[$spos + $j] . chr(0);
                                    }
                                    $charsLeft -= $len;
                                    $asciiEncoding = false;
                                } else {
                                    $newstr = '';
                                    for ($j = 0; $j < strlen($retstr); $j++) {
                                        $newstr = $retstr[$j] . chr(0);
                                    }
                                    $retstr = $newstr;
                                    $len = min($charsLeft * 2, $limitpos - $spos); // min($charsLeft, $conlength);
                                    $retstr .= substr($data, $spos, $len);
                                    $charsLeft -= $len / 2;
                                    $asciiEncoding = false;
                                }
                                $spos += $len;
                            }
                        }
                        $retstr = ($asciiEncoding) ? $retstr : $this->_encodeUTF16($retstr);

                        if ($richString) {
                            $spos += 4 * $formattingRuns;
                        }

                        // For extended strings, skip over the extended string data
                        if ($extendedString) {
                            $spos += $extendedRunLength;
                        }
                        $this->sst[] = $retstr;
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FILEPASS:
                    return false;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NAME:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMAT:
                    $indexCode = v($data, $pos + 4);
                    if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
                        $numchars = v($data, $pos + 6);
                        if (ord($data[$pos + 8]) == 0) {
                            $formatString = substr($data, $pos + 9, $numchars);
                        } else {
                            $formatString = substr($data, $pos + 9, $numchars * 2);
                        }
                    } else {
                        $numchars = ord($data[$pos + 6]);
                        $formatString = substr($data, $pos + 7, $numchars * 2);
                    }
                    $this->formatRecords[$indexCode] = $formatString;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FONT:
                    $height = v($data, $pos + 4);
                    $option = v($data, $pos + 6);
                    $color = v($data, $pos + 8);
                    $weight = v($data, $pos + 10);
                    $under = ord($data[$pos + 14]);
                    $font = "";
                    // Font name
                    $numchars = ord($data[$pos + 18]);
                    if ((ord($data[$pos + 19]) & 1) == 0) {
                        $font = substr($data, $pos + 20, $numchars);
                    } else {
                        $font = substr($data, $pos + 20, $numchars * 2);
                        $font = $this->_encodeUTF16($font);
                    }
                    $this->fontRecords[] = array(
                        'height' => $height / 20,
                        'italic' => !!($option & 2),
                        'color' => $color,
                        'under' => !($under == 0),
                        'bold' => ($weight == 700),
                        'font' => $font,
                        'raw' => $this->dumpHexData($data, $pos + 3, $length)
                    );
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_PALETTE:
                    $colors = ord($data[$pos + 4]) | ord($data[$pos + 5]) << 8;
                    for ($coli = 0; $coli < $colors; $coli++) {
                        $colOff = $pos + 2 + ($coli * 4);
                        $colr = ord($data[$colOff]);
                        $colg = ord($data[$colOff + 1]);
                        $colb = ord($data[$colOff + 2]);
                        $this->colors[0x07 + $coli] = '#' . $this->myhex($colr) . $this->myhex($colg) . $this->myhex($colb);
                    }
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_XF:
                    $fontIndexCode = (ord($data[$pos + 4]) | ord($data[$pos + 5]) << 8) - 1;
                    $fontIndexCode = max(0, $fontIndexCode);
                    $indexCode = ord($data[$pos + 6]) | ord($data[$pos + 7]) << 8;
                    $alignbit = ord($data[$pos + 10]) & 3;
                    $bgi = (ord($data[$pos + 22]) | ord($data[$pos + 23]) << 8) & 0x3FFF;
                    $bgcolor = ($bgi & 0x7F);
//						$bgcolor = ($bgi & 0x3f80) >> 7;
                    $align = "";
                    if ($alignbit == 3) {
                        $align = "right";
                    }
                    if ($alignbit == 2) {
                        $align = "center";
                    }

                    $fillPattern = (ord($data[$pos + 21]) & 0xFC) >> 2;
                    if ($fillPattern == 0) {
                        $bgcolor = "";
                    }

                    $xf = array();
                    $xf['formatIndex'] = $indexCode;
                    $xf['align'] = $align;
                    $xf['fontIndex'] = $fontIndexCode;
                    $xf['bgColor'] = $bgcolor;
                    $xf['fillPattern'] = $fillPattern;

                    $border = ord($data[$pos + 14]) | (ord($data[$pos + 15]) << 8) | (ord($data[$pos + 16]) << 16) | (ord($data[$pos + 17]) << 24);
                    $xf['borderLeft'] = $this->lineStyles[($border & 0xF)];
                    $xf['borderRight'] = $this->lineStyles[($border & 0xF0) >> 4];
                    $xf['borderTop'] = $this->lineStyles[($border & 0xF00) >> 8];
                    $xf['borderBottom'] = $this->lineStyles[($border & 0xF000) >> 12];

                    $xf['borderLeftColor'] = ($border & 0x7F0000) >> 16;
                    $xf['borderRightColor'] = ($border & 0x3F800000) >> 23;
                    $border = (ord($data[$pos + 18]) | ord($data[$pos + 19]) << 8);

                    $xf['borderTopColor'] = ($border & 0x7F);
                    $xf['borderBottomColor'] = ($border & 0x3F80) >> 7;

                    if (array_key_exists($indexCode, $this->dateFormats)) {
                        $xf['type'] = 'date';
                        $xf['format'] = $this->dateFormats[$indexCode];
                        if ($align == '') {
                            $xf['align'] = 'right';
                        }
                    } elseif (array_key_exists($indexCode, $this->numberFormats)) {
                        $xf['type'] = 'number';
                        $xf['format'] = $this->numberFormats[$indexCode];
                        if ($align == '') {
                            $xf['align'] = 'right';
                        }
                    } else {
                        $isdate = FALSE;
                        $formatstr = '';
                        if ($indexCode > 0) {
                            if (isset($this->formatRecords[$indexCode]))
                                $formatstr = $this->formatRecords[$indexCode];
                            if ($formatstr != "") {
                                $tmp = preg_replace("/\;.*/", "", $formatstr);
                                $tmp = preg_replace("/^\[[^\]]*\]/", "", $tmp);
                                if (preg_match("/[^hmsday\/\-:\s\\\,AMP]/i", $tmp) == 0) { // found day and time format
                                    $isdate = TRUE;
                                    $formatstr = $tmp;
                                    $formatstr = str_replace(array('AM/PM', 'mmmm', 'mmm'), array('a', 'F', 'M'), $formatstr);
                                    // m/mm are used for both minutes and months - oh SNAP!
                                    // This mess tries to fix for that.
                                    // 'm' == minutes only if following h/hh or preceding s/ss
                                    $formatstr = preg_replace("/(h:?)mm?/", "$1i", $formatstr);
                                    $formatstr = preg_replace("/mm?(:?s)/", "i$1", $formatstr);
                                    // A single 'm' = n in PHP
                                    $formatstr = preg_replace("/(^|[^m])m([^m]|$)/", '$1n$2', $formatstr);
                                    $formatstr = preg_replace("/(^|[^m])m([^m]|$)/", '$1n$2', $formatstr);
                                    // else it's months
                                    $formatstr = str_replace('mm', 'm', $formatstr);
                                    // Convert single 'd' to 'j'
                                    $formatstr = preg_replace("/(^|[^d])d([^d]|$)/", '$1j$2', $formatstr);
                                    $formatstr = str_replace(array('dddd', 'ddd', 'dd', 'yyyy', 'yy', 'hh', 'h'), array('l', 'D', 'd', 'Y', 'y', 'H', 'g'), $formatstr);
                                    $formatstr = preg_replace("/ss?/", 's', $formatstr);
                                }
                            }
                        }
                        if ($isdate) {
                            $xf['type'] = 'date';
                            $xf['format'] = $formatstr;
                            if ($align == '') {
                                $xf['align'] = 'right';
                            }
                        } else {
                            // If the format string has a 0 or # in it, we'll assume it's a number
                            if (preg_match("/[0#]/", $formatstr)) {
                                $xf['type'] = 'number';
                                if ($align == '') {
                                    $xf['align'] = 'right';
                                }
                            } else {
                                $xf['type'] = 'other';
                            }
                            $xf['format'] = $formatstr;
                            $xf['code'] = $indexCode;
                        }
                    }
                    $this->xfRecords[] = $xf;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR:
                    $this->nineteenFour = (ord($data[$pos + 4]) == 1);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET:
                    $rec_offset = $this->_GetInt4d($data, $pos + 4);
                    $rec_typeFlag = ord($data[$pos + 8]);
                    $rec_visibilityFlag = ord($data[$pos + 9]);
                    $rec_length = ord($data[$pos + 10]);

                    if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
                        $chartype = ord($data[$pos + 11]);
                        if ($chartype == 0) {
                            $rec_name = substr($data, $pos + 12, $rec_length);
                        } else {
                            $rec_name = $this->_encodeUTF16(substr($data, $pos + 12, $rec_length * 2));
                        }
                    } elseif ($version == SPREADSHEET_EXCEL_READER_BIFF7) {
                        $rec_name = substr($data, $pos + 11, $rec_length);
                    }
                    $this->boundsheets[] = array('name' => $rec_name, 'offset' => $rec_offset);
                    break;
            }

            $pos += $length + 4;
            $code = ord($data[$pos]) | ord($data[$pos + 1]) << 8;
            $length = ord($data[$pos + 2]) | ord($data[$pos + 3]) << 8;
        }

        foreach ($this->boundsheets as $key => $val) {
            $this->sn = $key;
            $this->_parsesheet($val['offset']);
        }
        return true;
    }

    /**
     * Parse a worksheet
     */
    function _parsesheet($spos) {
        $cont = true;
        $data = $this->data;
        // read BOF
        $code = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
        $length = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;

        $version = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
        $substreamType = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;

        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) && ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return -1;
        }

        if ($substreamType != SPREADSHEET_EXCEL_READER_WORKSHEET) {
            return -2;
        }
        $spos += $length + 4;
        while ($cont) {
            $lowcode = ord($data[$spos]);
            if ($lowcode == SPREADSHEET_EXCEL_READER_TYPE_EOF)
                break;
            $code = $lowcode | ord($data[$spos + 1]) << 8;
            $length = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
            $spos += 4;
            $this->sheets[$this->sn]['maxrow'] = $this->_rowoffset - 1;
            $this->sheets[$this->sn]['maxcol'] = $this->_coloffset - 1;
            unset($this->rectype);
            switch ($code) {
                case SPREADSHEET_EXCEL_READER_TYPE_DIMENSION:
                    if (!isset($this->numRows)) {
                        if (($length == 10) || ($version == SPREADSHEET_EXCEL_READER_BIFF7)) {
                            $this->sheets[$this->sn]['numRows'] = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                            $this->sheets[$this->sn]['numCols'] = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;
                        } else {
                            $this->sheets[$this->sn]['numRows'] = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                            $this->sheets[$this->sn]['numCols'] = ord($data[$spos + 10]) | ord($data[$spos + 11]) << 8;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS:
                    $cellRanges = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    for ($i = 0; $i < $cellRanges; $i++) {
                        $fr = ord($data[$spos + 8 * $i + 2]) | ord($data[$spos + 8 * $i + 3]) << 8;
                        $lr = ord($data[$spos + 8 * $i + 4]) | ord($data[$spos + 8 * $i + 5]) << 8;
                        $fc = ord($data[$spos + 8 * $i + 6]) | ord($data[$spos + 8 * $i + 7]) << 8;
                        $lc = ord($data[$spos + 8 * $i + 8]) | ord($data[$spos + 8 * $i + 9]) << 8;
                        if ($lr - $fr > 0) {
                            $this->sheets[$this->sn]['cellsInfo'][$fr + 1][$fc + 1]['rowspan'] = $lr - $fr + 1;
                        }
                        if ($lc - $fc > 0) {
                            $this->sheets[$this->sn]['cellsInfo'][$fr + 1][$fc + 1]['colspan'] = $lc - $fc + 1;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_RK:
                case SPREADSHEET_EXCEL_READER_TYPE_RK2:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $rknum = $this->_GetInt4d($data, $spos + 6);
                    $numValue = $this->_GetIEEE754($rknum);
                    $info = $this->_getCellDetails($spos, $numValue, $column);
                    $this->addcell($row, $column, $info['string'], $info);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABELSST:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $xfindex = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    $index = $this->_GetInt4d($data, $spos + 6);
                    $this->addcell($row, $column, $this->sst[$index], array('xfIndex' => $xfindex));
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULRK:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $colFirst = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $colLast = ord($data[$spos + $length - 2]) | ord($data[$spos + $length - 1]) << 8;
                    $columns = $colLast - $colFirst + 1;
                    $tmppos = $spos + 4;
                    for ($i = 0; $i < $columns; $i++) {
                        $numValue = $this->_GetIEEE754($this->_GetInt4d($data, $tmppos + 2));
                        $info = $this->_getCellDetails($tmppos - 4, $numValue, $colFirst + $i + 1);
                        $tmppos += 6;
                        $this->addcell($row, $colFirst + $i, $info['string'], $info);
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NUMBER:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $tmp = unpack("ddouble", substr($data, $spos + 6, 8)); // It machine machine dependent
                    if ($this->isDate($spos)) {
                        $numValue = $tmp['double'];
                    } else {
                        $numValue = $this->createNumber($spos);
                    }
                    $info = $this->_getCellDetails($spos, $numValue, $column);
                    $this->addcell($row, $column, $info['string'], $info);
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA:
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA2:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    if ((ord($data[$spos + 6]) == 0) && (ord($data[$spos + 12]) == 255) && (ord($data[$spos + 13]) == 255)) {
                        //String formula. Result follows in a STRING record
                        // This row/col are stored to be referenced in that record
                        // http://code.google.com/p/php-excel-reader/issues/detail?id=4
                        $previousRow = $row;
                        $previousCol = $column;
                    } elseif ((ord($data[$spos + 6]) == 1) && (ord($data[$spos + 12]) == 255) && (ord($data[$spos + 13]) == 255)) {
                        //Boolean formula. Result is in +2; 0=false,1=true
                        // http://code.google.com/p/php-excel-reader/issues/detail?id=4
                        if (ord($this->data[$spos + 8]) == 1) {
                            $this->addcell($row, $column, "TRUE");
                        } else {
                            $this->addcell($row, $column, "FALSE");
                        }
                    } elseif ((ord($data[$spos + 6]) == 2) && (ord($data[$spos + 12]) == 255) && (ord($data[$spos + 13]) == 255)) {
                        //Error formula. Error code is in +2;
                    } elseif ((ord($data[$spos + 6]) == 3) && (ord($data[$spos + 12]) == 255) && (ord($data[$spos + 13]) == 255)) {
                        //Formula result is a null string.
                        $this->addcell($row, $column, '');
                    } else {
                        // result is a number, so first 14 bytes are just like a _NUMBER record
                        $tmp = unpack("ddouble", substr($data, $spos + 6, 8)); // It machine machine dependent
                        if ($this->isDate($spos)) {
                            $numValue = $tmp['double'];
                        } else {
                            $numValue = $this->createNumber($spos);
                        }
                        $info = $this->_getCellDetails($spos, $numValue, $column);
                        $this->addcell($row, $column, $info['string'], $info);
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOOLERR:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $string = ord($data[$spos + 6]);
                    $this->addcell($row, $column, $string);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_STRING:
                    // http://code.google.com/p/php-excel-reader/issues/detail?id=4
                    if ($version == SPREADSHEET_EXCEL_READER_BIFF8) {
                        // Unicode 16 string, like an SST record
                        $xpos = $spos;
                        $numChars = ord($data[$xpos]) | (ord($data[$xpos + 1]) << 8);
                        $xpos += 2;
                        $optionFlags = ord($data[$xpos]);
                        $xpos++;
                        $asciiEncoding = (($optionFlags & 0x01) == 0);
                        $extendedString = (($optionFlags & 0x04) != 0);
                        // See if string contains formatting information
                        $richString = (($optionFlags & 0x08) != 0);
                        if ($richString) {
                            // Read in the crun
                            $formattingRuns = ord($data[$xpos]) | (ord($data[$xpos + 1]) << 8);
                            $xpos += 2;
                        }
                        if ($extendedString) {
                            // Read in cchExtRst
                            $extendedRunLength = $this->_GetInt4d($this->data, $xpos);
                            $xpos += 4;
                        }
                        $len = ($asciiEncoding) ? $numChars : $numChars * 2;
                        $retstr = substr($data, $xpos, $len);
                        $xpos += $len;
                        $retstr = ($asciiEncoding) ? $retstr : $this->_encodeUTF16($retstr);
                    } elseif ($version == SPREADSHEET_EXCEL_READER_BIFF7) {
                        // Simple byte string
                        $xpos = $spos;
                        $numChars = ord($data[$xpos]) | (ord($data[$xpos + 1]) << 8);
                        $xpos += 2;
                        $retstr = substr($data, $xpos, $numChars);
                    }
                    $this->addcell($previousRow, $previousCol, $retstr);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_ROW:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $rowInfo = ord($data[$spos + 6]) | ((ord($data[$spos + 7]) << 8) & 0x7FFF);
                    if (($rowInfo & 0x8000) > 0) {
                        $rowHeight = -1;
                    } else {
                        $rowHeight = $rowInfo & 0x7FFF;
                    }
                    $rowHidden = (ord($data[$spos + 12]) & 0x20) >> 5;
                    $this->rowInfo[$this->sn][$row + 1] = Array('height' => $rowHeight / 20, 'hidden' => $rowHidden);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_DBCELL:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULBLANK:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $cols = ($length / 2) - 3;
                    for ($c = 0; $c < $cols; $c++) {
                        $xfindex = ord($data[$spos + 4 + ($c * 2)]) | ord($data[$spos + 5 + ($c * 2)]) << 8;
                        $this->addcell($row, $column + $c, "", array('xfIndex' => $xfindex));
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABEL:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $this->addcell($row, $column, substr($data, $spos + 8, ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8));
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_EOF:
                    $cont = false;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_HYPER:
                    //  Only handle hyperlinks to a URL
                    $row = ord($this->data[$spos]) | ord($this->data[$spos + 1]) << 8;
                    $row2 = ord($this->data[$spos + 2]) | ord($this->data[$spos + 3]) << 8;
                    $column = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
                    $column2 = ord($this->data[$spos + 6]) | ord($this->data[$spos + 7]) << 8;
                    $linkdata = Array();
                    $flags = ord($this->data[$spos + 28]);
                    $udesc = "";
                    $ulink = "";
                    $uloc = 32;
                    $linkdata['flags'] = $flags;
                    if (($flags & 1) > 0) {   // is a type we understand
                        //  is there a description ?
                        if (($flags & 0x14) == 0x14) {   // has a description
                            $uloc += 4;
                            $descLen = ord($this->data[$spos + 32]) | ord($this->data[$spos + 33]) << 8;
                            $udesc = substr($this->data, $spos + $uloc, $descLen * 2);
                            $uloc += 2 * $descLen;
                        }
                        $ulink = $this->read16bitstring($this->data, $spos + $uloc + 20);
                        if ($udesc == "") {
                            $udesc = $ulink;
                        }
                    }
                    $linkdata['desc'] = $udesc;
                    $linkdata['link'] = $this->_encodeUTF16($ulink);
                    for ($r = $row; $r <= $row2; $r++) {
                        for ($c = $column; $c <= $column2; $c++) {
                            $this->sheets[$this->sn]['cellsInfo'][$r + 1][$c + 1]['hyperlink'] = $linkdata;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_DEFCOLWIDTH:
                    $this->defaultColWidth = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_STANDARDWIDTH:
                    $this->standardColWidth = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_COLINFO:
                    $colfrom = ord($data[$spos + 0]) | ord($data[$spos + 1]) << 8;
                    $colto = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $cw = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    $cxf = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;
                    $co = ord($data[$spos + 8]);
                    for ($coli = $colfrom; $coli <= $colto; $coli++) {
                        $this->colInfo[$this->sn][$coli + 1] = Array('width' => $cw, 'xf' => $cxf, 'hidden' => ($co & 0x01), 'collapsed' => ($co & 0x1000) >> 12);
                    }
                    break;

                default:
                    break;
            }
            $spos += $length;
        }

        if (!isset($this->sheets[$this->sn]['numRows']))
            $this->sheets[$this->sn]['numRows'] = $this->sheets[$this->sn]['maxrow'];
        if (!isset($this->sheets[$this->sn]['numCols']))
            $this->sheets[$this->sn]['numCols'] = $this->sheets[$this->sn]['maxcol'];
    }

    function isDate($spos) {
        $xfindex = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
        return ($this->xfRecords[$xfindex]['type'] == 'date');
    }

    // Get the details for a particular cell
    function _getCellDetails($spos, $numValue, $column) {
        $xfindex = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
        $xfrecord = $this->xfRecords[$xfindex];
        $type = $xfrecord['type'];

        $format = $xfrecord['format'];
        $formatIndex = $xfrecord['formatIndex'];
        $fontIndex = $xfrecord['fontIndex'];
        $formatColor = "";
        $rectype = '';
        $string = '';
        $raw = '';

        if (isset($this->_columnsFormat[$column + 1])) {
            $format = $this->_columnsFormat[$column + 1];
        }

        if ($type == 'date') {
            // See http://groups.google.com/group/php-excel-reader-discuss/browse_frm/thread/9c3f9790d12d8e10/f2045c2369ac79de
            $rectype = 'date';
            // Convert numeric value into a date
            $utcDays = floor($numValue - ($this->nineteenFour ? SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904 : SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS));
            $utcValue = ($utcDays) * SPREADSHEET_EXCEL_READER_MSINADAY;
            $dateinfo = gmgetdate($utcValue);

            $raw = $numValue;
            $fractionalDay = $numValue - floor($numValue) + .0000001; // The .0000001 is to fix for php/excel fractional diffs

            $totalseconds = floor(SPREADSHEET_EXCEL_READER_MSINADAY * $fractionalDay);
            $secs = $totalseconds % 60;
            $totalseconds -= $secs;
            $hours = floor($totalseconds / (60 * 60));
            $mins = floor($totalseconds / 60) % 60;
            $string = date($format, mktime($hours, $mins, $secs, $dateinfo["mon"], $dateinfo["mday"], $dateinfo["year"]));
        } else if ($type == 'number') {
            $rectype = 'number';
            $formatted = $this->_format_value($format, $numValue, $formatIndex);
            $string = $formatted['string'];
            $formatColor = $formatted['formatColor'];
            $raw = $numValue;
        } else {
            if ($format == "") {
                $format = $this->_defaultFormat;
            }
            $rectype = 'unknown';
            $formatted = $this->_format_value($format, $numValue, $formatIndex);
            $string = $formatted['string'];
            $formatColor = $formatted['formatColor'];
            $raw = $numValue;
        }

        return array(
            'string' => $string,
            'raw' => $raw,
            'rectype' => $rectype,
            'format' => $format,
            'formatIndex' => $formatIndex,
            'fontIndex' => $fontIndex,
            'formatColor' => $formatColor,
            'xfIndex' => $xfindex
        );
    }

    function createNumber($spos) {
        $rknumhigh = $this->_GetInt4d($this->data, $spos + 10);
        $rknumlow = $this->_GetInt4d($this->data, $spos + 6);
        $sign = ($rknumhigh & 0x80000000) >> 31;
        $exp = ($rknumhigh & 0x7ff00000) >> 20;
        $mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
        $mantissalow1 = ($rknumlow & 0x80000000) >> 31;
        $mantissalow2 = ($rknumlow & 0x7fffffff);
        $value = $mantissa / pow(2, (20 - ($exp - 1023)));
        if ($mantissalow1 != 0)
            $value += 1 / pow(2, (21 - ($exp - 1023)));
        $value += $mantissalow2 / pow(2, (52 - ($exp - 1023)));
        if ($sign) {
            $value = -1 * $value;
        }
        return $value;
    }

    function addcell($row, $col, $string, $info = null) {
        $this->sheets[$this->sn]['maxrow'] = max($this->sheets[$this->sn]['maxrow'], $row + $this->_rowoffset);
        $this->sheets[$this->sn]['maxcol'] = max($this->sheets[$this->sn]['maxcol'], $col + $this->_coloffset);
        $this->sheets[$this->sn]['cells'][$row + $this->_rowoffset][$col + $this->_coloffset] = $string;
        if ($this->store_extended_info && $info) {
            foreach ($info as $key => $val) {
                $this->sheets[$this->sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset][$key] = $val;
            }
        }
    }

    function _GetIEEE754($rknum) {
        if (($rknum & 0x02) != 0) {
            $value = $rknum >> 2;
        } else {
            //mmp
            // I got my info on IEEE754 encoding from
            // http://research.microsoft.com/~hollasch/cgindex/coding/ieeefloat.html
            // The RK format calls for using only the most significant 30 bits of the
            // 64 bit floating point value. The other 34 bits are assumed to be 0
            // So, we use the upper 30 bits of $rknum as follows...
            $sign = ($rknum & 0x80000000) >> 31;
            $exp = ($rknum & 0x7ff00000) >> 20;
            $mantissa = (0x100000 | ($rknum & 0x000ffffc));
            $value = $mantissa / pow(2, (20 - ($exp - 1023)));
            if ($sign) {
                $value = -1 * $value;
            }
            //end of changes by mmp
        }
        if (($rknum & 0x01) != 0) {
            $value /= 100;
        }
        return $value;
    }

    function _encodeUTF16($string) {
        $result = $string;
        // echo $this->_defaultEncoding;
        if ($this->_defaultEncoding) {
            switch ($this->_encoderFunction) {

                case 'iconv' :
                    $result = mb_convert_encoding($string, 'UTF-8', 'UTF-16LE');
                    //$result = iconv('UTF-16LE', $this->_defaultEncoding, $string);
                    break;
                case 'mb_convert_encoding' : $result = mb_convert_encoding($string, $this->_defaultEncoding, 'UTF-16LE');
                    break;
            }
        }
        return $result;
    }

    function _GetInt4d($data, $pos) {
        $value = ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | (ord($data[$pos + 3]) << 24);
        if ($value >= 4294967294) {
            $value = -2;
        }
        return $value;
    }

}

?>
