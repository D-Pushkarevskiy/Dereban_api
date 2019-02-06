<?php

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function PrepStr($str, $length = 0, $more_symbols = "") {
   $res = $str;
   $res = preg_replace("/'/", "", $res);
   $res = preg_replace("/\"/", "", $res);
   $res = str_replace("\\", "", $res);
   if ($more_symbols != "") {
      for ($i = 0; $i < strlen($more_symbols); $i++) {
         $res = str_replace($more_symbols[$i], "", $res);
      }
   }
   if ($length != 0)
      $res = substr($res, 0, $length);

   return $res;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function QStr($str = "") {
   return "'" . $str . "'";
}

function DQStr($str = "") {
   return "\"" . $str . "\"";
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function QPrepStr($str, $length = 0, $more_symbols = "") {
   return QStr(PrepStr($str, $length, $more_symbols));
}

function DQPrepStr($str, $length = 0, $more_symbols = "") {
   return DQStr(PrepStr($str, $length, $more_symbols));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function QPrepStrFS($str, $length = 0, $more_symbols = "") {
   if ($str[strlen($str) - 1] == "!") {
      $str = substr($str, 0, -1);
      $last_symb = "";
   } else
      $last_symb = "%";
   return QStr(preg_replace("/\*/", "%", PrepStr($str, $length, $more_symbols)) . $last_symb);
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function FormatField($fval, $ftype) {
   $res = $fval;
   switch ($ftype) {
      case "integer":
         $res = intval($fval);
         break;
      case "float":
         $res = number_format($fval, 4, '.', '');
         break;
      case "float2":
         $res = number_format($fval, 2, '.', '');
         break;
      case "float3":
         $res = number_format($fval, 3, '.', '');
         break;
      case "float4":
         $res = number_format($fval, 4, '.', '');
         break;
      case "float5":
         $res = number_format($fval, 5, '.', '');
         break;
      case "floatKM":
         if (round(abs($fval), -5) >= 1000000) {
            $res = round($fval, -3) / 1000000 . 'M';
         } elseif (round(abs($fval), -2) >= 1000) {
            $res = round($fval, -2) / 1000 . 'K';
         } else {
            $res = intval($fval);
         }
         break;
      case "usamoneyspace":
         $res = number_format($fval, 2, '.', ' ');
         break;
      case "usamoneycomma":
         $res = number_format($fval, 2, '.', ',');
         break;
      case "percent":
         if ($fval == "")
            $fval = 0;
         $res = number_format($fval * 100, 2, '.', '') . "%";
         break;
      case "usadate":
         $res = gmdate("m/d/Y", $fval);
         if ($res == "01/01/1970")
            $res = "";
         break;
      case "usadatetime":
         $res = gmdate("m/d/Y H:i:s", $fval);
         if ($res == "01/01/1970 00:00:00")
            $res = "";
         break;
      case "percent_null":
         if (is_numeric($res)) {
            $res = number_format($fval * 100, 2, '.', '') . "%";
         }
         break;
      case "string":
         $res = $fval;
         break;
      case "date":
         $res = gmdate("d.m.Y", $fval);
         if ($res == "01.01.1970")
            $res = "";
         break;
      case "date_dd/mm/yyyy":
         $res = gmdate("d/m/Y", $fval);
         if ($res == "01/01/1970")
            $res = "";
         break;
      case "date_2sort":
         $res = gmdate("Y-m-d", $fval);
         break;
      case "datetime_2sort":
         $res = gmdate("Y-m-d-H-i-s", $fval);
         break;
      case "time":
         $res = gmdate("H:i:s", $fval);
         break;
      case "stime":
         if (($fval == "0") || ($fval == ""))
            $res = "";
         else
            $res = gmdate("H:i:s", $fval);
         break;
      case "etime":
         if (($fval == "86399") || ($fval == ""))
            $res = "";
         else
            $res = gmdate("H:i:s", $fval);
         break;
      case "datetime":
         $res = gmdate("d.m.Y H:i:s", $fval);
         if ($res == "01.01.1970 00:00:00")
            $res = "";
         break;
      case "datetime_ms":
         $pieces = explode('.', $fval);
         $u = str_pad(substr($pieces[1], 0, 3), 3, "0");
         $res = gmdate("d.m.Y H:i:s", $pieces[0]) . ".$u";
         if ($res == "01.01.1970 00:00:00.000")
            $res = "";
         break;
      case "boolean":
         $res = ($fval == "0" ? _yes : _no);
         break;
      case "week":
         $week = "";
         if ((!isset($fval)) || ($fval == "") || ($fval == "null") || ($fval == "127") || ($fval == "255")) {
            //$fval = 255;
            $res = "";
         } else {
            if (($fval & 1) == 1)
               $week .= _Mon . ",";
            if (($fval & 2) == 2)
               $week .= _Tue . ",";
            if (($fval & 4) == 4)
               $week .= _Wed . ",";
            if (($fval & 8) == 8)
               $week .= _Thu . ",";
            if (($fval & 16) == 16)
               $week .= _Fri . ",";
            if (($fval & 32) == 32)
               $week .= _Sat . ",";
            if (($fval & 64) == 64)
               $week .= _Sun . ",";
            $res = substr($week, 0, strlen($week) - 1);
         }
         break;
      case "week_sun":
         $week = "";
         if (($fval & 2) == 2)
            $week .= _Mon . ",";
         if (($fval & 4) == 4)
            $week .= _Tue . ",";
         if (($fval & 8) == 8)
            $week .= _Wed . ",";
         if (($fval & 16) == 16)
            $week .= _Thu . ",";
         if (($fval & 32) == 32)
            $week .= _Fri . ",";
         if (($fval & 64) == 64)
            $week .= _Sat . ",";
         if (($fval & 1) == 1)
            $week .= _Sun . ",";
         $res = substr($week, 0, strlen($week) - 1);
         break;
      case "ip":
         $res = long2ip($fval);
         break;
   }
   return $res;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function CheckDateTimeOnly($datetime) {
   if (preg_match("/^\d\d\.\d\d\.\d\d\d\d \d\d:\d\d:\d\d$/", $datetime) == 1) {
      $day = intval(substr($datetime, 0, 2));
      $month = intval(substr($datetime, 3, 2));
      $year = intval(substr($datetime, 6, 4));
      $hour = intval(substr($datetime, 11, 2));
      $minute = intval(substr($datetime, 14, 2));
      $second = intval(substr($datetime, 17, 2));
   } else {
      return 1;
   }

   if ($year <= 1970)
      return 1;
   if (($month < 1) || ($month > 12))
      return 1;
   if (($hour < 0) || ($hour > 23))
      return 1;
   if (($minute < 0) || ($minute > 59))
      return 1;
   if (($second < 0) || ($second > 59))
      return 1;

   if (($year % 4 == 0) && (($year % 100 != 0) || ($year % 400 == 0))) {
      $daysinmonth = array(
          31,
          29,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   } else {
      $daysinmonth = array(
          31,
          28,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   }

   if (($day < 1) || ($day > $daysinmonth[$month - 1]))
      return 1;


   return 0;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function CheckDateTime($datetime) {
   if (preg_match("/^\d\d\.\d\d\.\d\d\d\d \d\d:\d\d:\d\d$/", $datetime) == 1) {
      $day = intval(substr($datetime, 0, 2));
      $month = intval(substr($datetime, 3, 2));
      $year = intval(substr($datetime, 6, 4));
      $hour = intval(substr($datetime, 11, 2));
      $minute = intval(substr($datetime, 14, 2));
      $second = intval(substr($datetime, 17, 2));
   } elseif (preg_match("/^\d\d\.\d\d\.\d\d\d\d$/", $datetime) == 1) {
      $day = intval(substr($datetime, 0, 2));
      $month = intval(substr($datetime, 3, 2));
      $year = intval(substr($datetime, 6, 4));

      $hour = 0;
      $minute = 0;
      $second = 0;
   } else {
      return 1;
   }

   if ($year <= 1970)
      return 1;
   if (($month < 1) || ($month > 12))
      return 1;
   if (($hour < 0) || ($hour > 23))
      return 1;
   if (($minute < 0) || ($minute > 59))
      return 1;
   if (($second < 0) || ($second > 59))
      return 1;

   if (($year % 4 == 0) && (($year % 100 != 0) || ($year % 400 == 0))) {
      $daysinmonth = array(
          31,
          29,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   } else {
      $daysinmonth = array(
          31,
          28,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   }

   if (($day < 1) || ($day > $daysinmonth[$month - 1]))
      return 1;


   return 0;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function CheckMyDate($date) {
   if (preg_match("/^\d\d\.\d\d\.\d\d\d\d$/", $date) == 1) {
      $day = intval(substr($date, 0, 2));
      $month = intval(substr($date, 3, 2));
      $year = intval(substr($date, 6, 4));

      $hour = 0;
      $minute = 0;
      $second = 0;
   } else {
      return 1;
   }

   if ($year <= 1970)
      return 1;
   if (($month < 1) || ($month > 12))
      return 1;
   if (($hour < 0) || ($hour > 23))
      return 1;
   if (($minute < 0) || ($minute > 59))
      return 1;
   if (($second < 0) || ($second > 59))
      return 1;

   if (($year % 4 == 0) && (($year % 100 != 0) || ($year % 400 == 0))) {
      $daysinmonth = array(
          31,
          29,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   } else {
      $daysinmonth = array(
          31,
          28,
          31,
          30,
          31,
          30,
          31,
          31,
          30,
          31,
          30,
          31);
   }

   if (($day < 1) || ($day > $daysinmonth[$month - 1]))
      return 1;


   return 0;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function CheckTime($time) {
   if (preg_match("/^\d\d:\d\d:\d\d$/", $time) == 1) {
      $hour = intval(substr($time, 0, 2));
      $minute = intval(substr($time, 3, 2));
      $second = intval(substr($time, 6, 2));
   } else {
      return 1;
   }

   if (($hour < 0) || ($hour > 23))
      return 1;
   if (($minute < 0) || ($minute > 59))
      return 1;
   if (($second < 0) || ($second > 59))
      return 1;

   return 0;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StringToDateTime($date, $default_value = ""/* , $format = "d.m.Y H:i:s" */) {
   if ($date != "") {
      $day = substr($date, 0, 2);
      $month = substr($date, 3, 2);
      $year = substr($date, 6, 4);
      $hour = substr($date, 11, 2);
      $minute = substr($date, 14, 2);
      $second = substr($date, 17, 2);
      $date = gmmktime($hour + 24, $minute, $second, $month, $day, $year) - 24 * 60 * 60;
   } else
      $date = $default_value;
   return $date;
}

function StringToDate($date, $default_value = ""/* , $format = "d.m.Y" */) {
   if ($date != "") {
      $day = substr($date, 0, 2);
      $month = substr($date, 3, 2);
      $year = substr($date, 6, 4);
      $hour = 0;
      $minute = 0;
      $second = 0;
      $date = gmmktime($hour + 24, $minute, $second, $month, $day, $year) - 24 * 60 * 60;
   } else
      $date = $default_value;
   return $date;
}

function StringToTime($time, $default_value = ""/* , $format = "H:i:s" */) {
   if ($time != "") {
      $hours = intval(substr($time, 0, 2));
      $minutes = intval(substr($time, 3, 2));
      $seconds = intval(substr($time, 6, 2));
      $time = gmmktime($hours + 24, $minutes, $seconds, 1, 1, 1970) - 24 * 60 * 60;
   } else
      $time = $default_value;
   return $time;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function myfloatval($val) {
   return floatval(trim(str_replace(",", ".", $val)));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function myfloatval_null($val) {
   return ($val == "" ? "null" : myfloatval($val));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function intval_null($val) {
   return ($val == "" ? "null" : intval($val));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function percentval($val) {
   return min([1, max([-1, myfloatval($val) / 100])]);
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function percentval_null($val) {
   return ($val == "" ? "null" : percentval($val));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function percentval_positive($val) {
   return min([1, max([0, myfloatval($val) / 100])]);
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function percentval_positive_null($val) {
   return ($val == "" ? "null" : percentval_positive($val));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function checked0($val) {
   return ($val == "on" ? "0" : "1");
}

function checked1($val) {
   return ($val == "on" ? "1" : "0");
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function RTNames($rt_key, $rs_rt = null, $RULES = null) {
   if (is_null($rs_rt)) {
      if (!is_null($RULES)) {
         $requiredfilter_arr = GetRequiredFiltersFromSAArray($RULES, array(
             "",
             "",
             "rt_key",
             ""));
         $whereSArtkey = $requiredfilter_arr[2];
      } else {
         $whereSArtkey = "";
      }

      IncludeHelper("dbdata");
      $rs_rt = GetRSRoutTable($whereSArtkey);
   }

   $rt_str = "";
   $rs_rt->MoveFirst();
   while (!$rs_rt->EOF) {
      if (($rs_rt->fields[0] & $rt_key) != 0) {
         $rt_str .= $rs_rt->fields[1] . ", ";
      }
      $rs_rt->MoveNext();
   }
   //$rt_str = substr($rt_str,0,-2);

   return substr($rt_str, 0, -2);
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function RTValFromPOST($name_format, $id, $rs_rt = null, $RULES = null) {
   if (is_null($rs_rt)) {
      if (!is_null($RULES)) {
         $requiredfilter_arr = GetRequiredFiltersFromSAArray($RULES, array(
             "",
             "",
             "rt_key",
             ""));
         $whereSArtkey = $requiredfilter_arr[2];
      } else {
         $whereSArtkey = "";
      }

      IncludeHelper("dbdata");
      $rs_rt = GetRSRoutTable($whereSArtkey);
   }

   $nameformat_tags = array(
       "*RTKEY*",
       "*ID*");

   $route_value = 0;
   $rs_rt->MoveFirst();
   while (!$rs_rt->EOF) {

      $replace_tags = array(
          $rs_rt->fields[0],
          $id);
      $chname = str_replace($nameformat_tags, $replace_tags, $name_format);

      if ((isset($_POST["$chname"])) && ($_POST["$chname"] == "on")) {
         $tmp_val = $rs_rt->fields[0];
      } else {
         $tmp_val = 0;
      }
      $route_value = $route_value | $tmp_val;

      $rs_rt->MoveNext();
   }

   return $route_value;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StripSlashN($str) {
   return str_replace(["\r\n",
       "\n\r",
       "\n",
       "\r"], "", $str);
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StrToWin1251($str, $param = "") {
   $result = iconv('UTF-8', 'WINDOWS-1251' . ($param != "" ? "//$param" : ""), $str);
   return $result;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StrToUtf8($str, $param = "") {
   $result = iconv('WINDOWS-1251', 'UTF-8' . ($param != "" ? "//$param" : ""), $str);
   return $result;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function MoneySumToString($sum, $lang = 'ua', $cur = 'eur', $fraction = true) {

   $main = [
       'ua' => [
           'eur' => [
               'nul' => 'нуль',
               'ten' => [
                   [
                       '',
                       'один',
                       'два',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть'],
                   [
                       '',
                       'одна',
                       'дв≥',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть']
               ],
               'a20' => [
                   'дес€ть',
                   'одинадц€ть',
                   'дванадц€ть',
                   'тринадц€ть',
                   'чотирнадц€ть',
                   'п`€тнадц€ть',
                   'ш≥стнадц€ть',
                   'с≥мнадц€ть',
                   'в≥с≥мнадц€ть',
                   'дев`€тнадц€ть'],
               'tens' => [
                   2 => 'двадц€ть',
                   'тридц€ть',
                   'сорок',
                   'п`€тдес€т',
                   'ш≥стдес€т',
                   'с≥мдес€т',
                   'в≥с≥мдес€т',
                   'дев`€носто'],
               'hundred' => [
                   '',
                   'сто',
                   'дв≥ст≥',
                   'триста',
                   'чотириста',
                   'п`€тсот',
                   'ш≥стсот',
                   'с≥мсот',
                   'в≥с≥мсот',
                   'дев€`тсот'],
               'unit' => [
                   [
                       'Ївроцент',
                       'Ївроценти',
                       'Ївроцент≥в',
                       1],
                   [
                       'Ївро',
                       'Ївро',
                       'Ївро',
                       0],
                   [
                       'тис€ча',
                       'тис€ч≥',
                       'тис€ч',
                       1],
                   [
                       'м≥льйон',
                       'м≥льйони',
                       'м≥льйон≥в',
                       0],
                   [
                       'м≥ль€рд',
                       'м≥ль€рди',
                       'м≥ль€рд≥в',
                       0],
               ]
           ],
           'usd' => [
               'nul' => 'нуль',
               'ten' => [
                   [
                       '',
                       'один',
                       'два',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть'],
                   [
                       '',
                       'одна',
                       'дв≥',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть']
               ],
               'a20' => [
                   'дес€ть',
                   'одинадц€ть',
                   'дванадц€ть',
                   'тринадц€ть',
                   'чотирнадц€ть',
                   'п`€тнадц€ть',
                   'ш≥стнадц€ть',
                   'с≥мнадц€ть',
                   'в≥с≥мнадц€ть',
                   'дев`€тнадц€ть'],
               'tens' => [
                   2 => 'двадц€ть',
                   'тридц€ть',
                   'сорок',
                   'п`€тдес€т',
                   'ш≥стдес€т',
                   'с≥мдес€т',
                   'в≥с≥мдес€т',
                   'дев`€носто'],
               'hundred' => [
                   '',
                   'сто',
                   'дв≥ст≥',
                   'триста',
                   'чотириста',
                   'п`€тсот',
                   'ш≥стсот',
                   'с≥мсот',
                   'в≥с≥мсот',
                   'дев€`тсот'],
               'unit' => [
                   [
                       'цент',
                       'центи',
                       'цент≥в',
                       1],
                   [
                       'дол. —Ўј',
                       'дол. —Ўј',
                       'дол. —Ўј',
                       0],
                   [
                       'тис€ча',
                       'тис€ч≥',
                       'тис€ч',
                       1],
                   [
                       'м≥льйон',
                       'м≥льйони',
                       'м≥льйон≥в',
                       0],
                   [
                       'м≥ль€рд',
                       'м≥ль€рди',
                       'м≥ль€рд≥в',
                       0],
               ]
           ],
           'uah' => [
               'nul' => 'нуль',
               'ten' => [
                   [
                       '',
                       'одна',
                       'дв≥',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть'],
                   [
                       '',
                       'одна',
                       'дв≥',
                       'три',
                       'чотири',
                       'п`€ть',
                       'ш≥сть',
                       'с≥м',
                       'в≥с≥м',
                       'дев`€ть']
               ],
               'a20' => [
                   'дес€ть',
                   'одинадц€ть',
                   'дванадц€ть',
                   'тринадц€ть',
                   'чотирнадц€ть',
                   'п`€тнадц€ть',
                   'ш≥стнадц€ть',
                   'с≥мнадц€ть',
                   'в≥с≥мнадц€ть',
                   'дев`€тнадц€ть'],
               'tens' => [
                   2 => 'двадц€ть',
                   'тридц€ть',
                   'сорок',
                   'п`€тдес€т',
                   'ш≥стдес€т',
                   'с≥мдес€т',
                   'в≥с≥мдес€т',
                   'дев`€носто'],
               'hundred' => [
                   '',
                   'сто',
                   'дв≥ст≥',
                   'триста',
                   'чотириста',
                   'п`€тсот',
                   'ш≥стсот',
                   'с≥мсот',
                   'в≥с≥мсот',
                   'дев€`тсот'],
               'unit' => [
                   [
                       'коп≥йка',
                       'коп≥йки',
                       'коп≥йок',
                       1],
                   [
                       'гривн€',
                       'гривн≥',
                       'гривень',
                       0],
                   [
                       'тис€ча',
                       'тис€ч≥',
                       'тис€ч',
                       1],
                   [
                       'м≥льйон',
                       'м≥льйони',
                       'м≥льйон≥в',
                       0],
                   [
                       'м≥ль€рд',
                       'м≥ль€рди',
                       'м≥ль€рд≥в',
                       0],
               ]
           ],
       ],
       'en' => [
           'eur' => [
               'nul' => 'zero',
               'ten' => [
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine'],
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine']
               ],
               'a20' => [
                   'ten',
                   'eleven',
                   'twelve',
                   'thirteen',
                   'fourteen',
                   'fifteen',
                   'sixteen',
                   'seventeen',
                   'eighteen',
                   'nineteen'],
               'tens' => [
                   2 => 'twenty',
                   'thirty',
                   'forty',
                   'fifty',
                   'sixty',
                   'seventy',
                   'eighty',
                   'ninety'],
               'hundred' => [
                   '',
                   'one hundred',
                   'two hundred',
                   'three hundred',
                   'four hundred',
                   'five hundred',
                   'six hundred',
                   'seven hundred',
                   'eight hundred',
                   'nine hundred'],
               'unit' => [
                   [
                       'Eurocent',
                       'cents',
                       'cents',
                       1],
                   [
                       'EUR',
                       'EUR',
                       'EUR',
                       0],
                   [
                       'thousand',
                       'thousands',
                       'thousands',
                       1],
                   [
                       'million',
                       'millions',
                       'millions',
                       0],
                   [
                       'billion',
                       'billions',
                       'billions',
                       0],
               ]
           ],
           'usd' => [
               'nul' => 'zero',
               'ten' => [
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine'],
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine']
               ],
               'a20' => [
                   'ten',
                   'eleven',
                   'twelve',
                   'thirteen',
                   'fourteen',
                   'fifteen',
                   'sixteen',
                   'seventeen',
                   'eighteen',
                   'nineteen'],
               'tens' => [
                   2 => 'twenty',
                   'thirty',
                   'forty',
                   'fifty',
                   'sixty',
                   'seventy',
                   'eighty',
                   'ninety'],
               'hundred' => [
                   '',
                   'one hundred',
                   'two hundred',
                   'three hundred',
                   'four hundred',
                   'five hundred',
                   'six hundred',
                   'seven hundred',
                   'eight hundred',
                   'nine hundred'],
               'unit' => [
                   [
                       'cent',
                       'cents',
                       'cents',
                       1],
                   [
                       'USD',
                       'USD',
                       'USD',
                       0],
                   [
                       'thousand',
                       'thousands',
                       'thousands',
                       1],
                   [
                       'million',
                       'millions',
                       'millions',
                       0],
                   [
                       'billion',
                       'billions',
                       'billions',
                       0],
               ]
           ],
           'uah' => [
               'nul' => 'zero',
               'ten' => [
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine'],
                   [
                       '',
                       'one',
                       'two',
                       'three',
                       'four',
                       'five',
                       'six',
                       'seven',
                       'eight',
                       'nine']
               ],
               'a20' => [
                   'ten',
                   'eleven',
                   'twelve',
                   'thirteen',
                   'fourteen',
                   'fifteen',
                   'sixteen',
                   'seventeen',
                   'eighteen',
                   'nineteen'],
               'tens' => [
                   2 => 'twenty',
                   'thirty',
                   'forty',
                   'fifty',
                   'sixty',
                   'seventy',
                   'eighty',
                   'ninety'],
               'hundred' => [
                   '',
                   'one hundred',
                   'two hundred',
                   'three hundred',
                   'four hundred',
                   'five hundred',
                   'six hundred',
                   'seven hundred',
                   'eight hundred',
                   'nine hundred'],
               'unit' => [
                   [
                       'cent',
                       'cents',
                       'cents',
                       1],
                   [
                       'UAH',
                       'UAH',
                       'UAH',
                       0],
                   [
                       'thousand',
                       'thousands',
                       'thousands',
                       1],
                   [
                       'million',
                       'millions',
                       'millions',
                       0],
                   [
                       'billion',
                       'billions',
                       'billions',
                       0],
               ]
           ],
       ],
   ];

   if (!function_exists('morph')) {

      function morph($n, $f1, $f2, $f5) {
         $n = abs(intval($n)) % 100;
         if ($n > 10 && $n < 20) {
            return $f5;
         }
         $n = $n % 10;
         if ($n > 1 && $n < 5) {
            return $f2;
         }
         if ($n == 1) {
            return $f1;
         }
         return $f5;
      }

   }

   list($val, $kop) = explode('.', sprintf("%015.2f", floatval($sum)));
   $out = array();
   if (intval($val) > 0) {
      foreach (str_split($val, 3) as $uk => $v) { // by 3 symbols
         if (!intval($v)) {
            continue;
         }
         $uk = sizeof($main[$lang][$cur]['unit']) - $uk - 1; // unit key
         $gender = $main[$lang][$cur]['unit'][$uk][3];
         list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
         // mega-logic
         $out[] = $main[$lang][$cur]['hundred'][$i1]; # 1xx-9xx
         if ($i2 > 1) {
            $out[] = $main[$lang][$cur]['tens'][$i2] . ' ' . $main[$lang][$cur]['ten'][$gender][$i3]; # 20-99
         } else {
            $out[] = $i2 > 0 ? $main[$lang][$cur]['a20'][$i3] : $main[$lang][$cur]['ten'][$gender][$i3]; # 10-19 | 1-9#
         }
         // units without rub & kop
         if ($uk > 1) {
            $out[] = morph($v, $main[$lang][$cur]['unit'][$uk][0], $main[$lang][$cur]['unit'][$uk][1], $main[$lang][$cur]['unit'][$uk][2]);
         }
      } //foreach
   } else {
      $out[] = $main[$lang][$cur]['nul'];
   }

   $out[] = morph(intval($val), $main[$lang][$cur]['unit'][1][0], $main[$lang][$cur]['unit'][1][1], $main[$lang][$cur]['unit'][1][2]); // rub

   if ($fraction) {
      $out[] = $kop . ' ' . morph($kop, $main[$lang][$cur]['unit'][0][0], $main[$lang][$cur]['unit'][0][1], $main[$lang][$cur]['unit'][0][2]); // kop
   }

   return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StrToWin1251OrOrig($str) {
   @$result = iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $str);
   if (!$result) {
      @$result = iconv('UTF-8', 'WINDOWS-1251//IGNORE', $str);
   }
   if (!$result) {
      return $str;
   }
   return $result;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

function StrToUtf8OrOrig($str) {
   @$result = iconv('WINDOWS-1251', 'UTF-8//TRANSLIT', $str);
   if (!$result) {
      @$result = iconv('WINDOWS-1251', 'UTF-8//IGNORE', $str);
   }
   if (!$result) {
      return $str;
   }
   return $result;
}

////////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////
?>