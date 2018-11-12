<?php

function bawp_query($query, $log = false, $send_slave = 1)
{
    //if (bawp_query($query))
    try {
        if ($log) mylog($query);
        $msc = microtime(true);
        $bawp_query = my_query($query, $send_slave);
        $msc = microtime(true) - $msc;
        if ($bawp_query === false) {
            mylog('mysql_error(): ' . mysql_error());
            throw new Exception("Query failed - " . mysql_error() . $query . json_encode(debug_backtrace()));
        }


        $sql_slow = 1.0;
        if (!$sql_slow) $sql_slow = 1.0;
        if ($msc > $sql_slow) {

            /*if (strtoupper(substr($query,0,6))=="SELECT" OR strtoupper(substr($query,0,6))=="UPDATE"){
                $result = my_query("EXPLAIN ".$query);
                $rows=fetch($result,true);
                $explain = json_encode($rows);
            } else */
            $explain = '';
            mylog($query . ' ' . $explain, number_format($msc, 6));
            diag('Message: ' . $query . ' took too long to execute', 'BAWP long query execution', Iq_DiagService::DIAG_SEVERITY_WARNING);
        }

    } catch (Exception $e) {
        mylog('query_error: ' . $e->getMessage());
        diag('Message: ' . $e->getMessage(), 'BAWP query error', Iq_DiagService::DIAG_SEVERITY_WARNING);
        return my_query("select 1");
    }
    return $bawp_query;
}

function fetch($result, $onerow = false, $onevalue = false, $onecolumn = false)
{
    $array = array();
    while ($row = mysql_fetch_assoc($result)) {
        if ($onecolumn)
            $array[] = reset($row);
        else {
            $array[] = $row;
        }
    }
    if ($onevalue) {
        if (isset($array[0])) {
            $values = array_values($array[0]);
            if (isset($values[0]))
                return $values[0];
            else return "";
        } else
            return "";
    }
    if ($onerow) {
        if (isset($array[0]))
            return $array[0];
        else
            return array();
    }
    return $array;
}

function datetime_sql_to_format($val, $opts)
{
    if (!valid_sqldate($val))
        return "";
    else {
        $date = $val;
        $date = date_create_from_format('Y-m-d H:i:s', $date);


        //$tz = new DateTimeZone('America/Los_Angeles');
        //$date->setTimezone($tz);

        // Allow empty strings or invalid dates
        if ($date) {
            return date_format($date, $opts);
        }
        return '';
    }
}

function date_sql_to_format($val, $opts)
{
    if (!valid_sqldate($val))
        return "";
    else {
        $date = explode(" ", $val);
        $date = date_create_from_format('Y-m-d', $date[0]);


        //$tz = new DateTimeZone('America/Los_Angeles');
        //$date->setTimezone($tz);

        // Allow empty strings or invalid dates
        if ($date) {
            return date_format($date, $opts);
        }
        return '';
    }
}

function time_sql_to_format($val, $opts)
{
    $time = date(TIME_FORMAT, strtotime($val));

    // Allow empty strings or invalid dates
    if ($time) {
        return $time;
    }
    return '';
}


function date_format_to_sql($val, $opts)
{
    if ($val == "_") {
        return $val;
    }
    $val = explode(" ", $val);
    $val = $val[0];
    $date = date_create_from_format($opts, $val);
    if ($date) {
        return date_format($date, 'Y-m-d');
    }
    return null;
}

function time_format_to_sql($val)
{
    return date("H:i:s", strtotime($val));
}

function datetime_format_to_sql($val, $opts)
{
    if ($val == "_") {
        return $val;
    }
    $date = date_create_from_format($opts, $val);
    if ($date) {
        return date_format($date, 'Y-m-d H:i:s');
    }
    return null;
}

require_once ROOTPATH . '/functions.php';