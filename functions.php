<?php

if (!defined('FAKE_MTURK')) {
    die('This is not a valid entry point.');
}

# Logging function. Timestamps and indents multiline messages.
function msg ($message) {
    $splat = preg_split("/[\r\n]/", rtrim($message));
    $first = array_shift($splat);
    $timestamp = date('[n-j-Y G:i:s] ');
    $rest = array_map(function ($content) use ($timestamp) {
        return str_repeat(' ', strlen($timestamp)) . $content;
    }, $splat);
    $arr = array_merge(array($timestamp . $first), $rest);
    $msg = implode("\n", $arr);
    error_log("$msg\n", 3, 'log.txt');
}

# Special logging function for arrays.
function msg_array ($array, $prefix = "") {
    msg($prefix . implode(preg_split("/[\r\n]/", var_export($array, 1)), "\n"));
}

# Parses a tab-delimited file and returns the result.
function parse_tsv_file ($filename) {
    $rows = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $result = array();
    if ($rows) {
        $columns = explode("\t", array_shift($rows));
        foreach ($rows as $row) {
            $split = explode("\t", $row);
            $result[] = array_combine($columns, explode("\t", $row));
        }
    }
    return $result;
}

# Repeats an array N times.
function array_repeat($arr, $times) {
    $result = array();
    foreach (range(1, $times) as $cnt) {
        foreach ($arr as $val) {
            $result[] = $val;
        }
    }
    return $result;
}

# Takes a slice of an associative array.
# http://www.php.net/manual/en/function.array-slice.php#64122
function array_slice_assoc($array, $keys) {
    return array_intersect_key($array,array_flip($keys));
}


# Special class that handles database access and quality of life improvements
class SQLitePDO extends PDO {
    function __construct($dbname) {
        try {
            parent::__construct("sqlite:${dbname}.sqlite");
        } catch (Exception $e) {
            die("Unable to connect: " . $e->getMessage());
        }

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); # pretty sure this can't throw

        # Instantiate tables.
        try {
            $this->beginTransaction();
            $this->exec("CREATE TABLE IF NOT EXISTS plan (assignmentId TEXT PRIMARY KEY, trial TEXT, hitId TEXT, workerId TEXT, turkSubmitTo TEXT, max_sequence INTEGER, list BLOB);");
            $this->exec("CREATE TABLE IF NOT EXISTS log (log_id INTEGER PRIMARY KEY, assignmentId TEXT NOT NULL, sequence INTEGER, type TEXT, result TEXT, timestamp TEXT, FOREIGN KEY(assignmentId) REFERENCES plan(assignmentId));");
            $this->commit();
        } catch (PDOException $e) {
            $this->rollBack();
            die("Transaction failed: " . $e->getMessage());
        }
    }

    # Convenience function for preparing and executing a single statement.
    # Wraps the statement in a transaction and rolls back on error for you.
    function exec1($query, $params=array(), $fetchtype=null) {
        try {
            $this->beginTransaction();
            $st = $this->prepare($query);
            $st->execute($params);
            $this->commit();
            # Only return results if caller has specified a fetch type.
            if (isset($fetchtype)) {
                return $st->fetchAll($fetchtype);
            }
        } catch (PDOException $e) {
            $this->rollBack();
            throw $e;
        }
    }

    # Begin data manip functions
    # --------------------------

    # Adds a plan to the plan table.
    function add_plan($assignment, $trial, $hit, $worker, $submit, $plan) {
        msg("adding plan for [$trial] $worker: $assignment");
        $this->exec1(
            'INSERT INTO plan(assignmentId, trial, hitId, workerId, turkSubmitTo, max_sequence, list) VALUES(?,?,?,?,?,?,?);',
            array($assignment, $trial, $hit, $worker, $submit, count($plan), json_encode($plan))
        );
    }

    # Appends something to the log table.
    function append_log($assignment, $sequence, $type, $result) {
        $this->exec1(
            'INSERT INTO log(assignmentId, sequence, type, result, timestamp) VALUES(?,?,?,?,datetime("now"));',
            array($assignment, $sequence, $type, $result)
        );
    }

    # Appends a result to the log table.
    function add_result($assignment, $result, $sequence) {
        msg("adding result $assignment seq: $sequence");
        $this->append_log($assignment, $sequence, 'completed', $result);
    }

    # Fetches the next sequence ID for a given assignment.
    function next_sequence_id($assignment) {
        msg('next_sequence_id called');
        # Grab all completed sequence IDs.
        $res = $this->exec1(
            'SELECT DISTINCT sequence as cur FROM log WHERE assignmentId=? AND type="completed";',
            array($assignment),
            PDO::FETCH_COLUMN # get the first column
        );

        # Sort the sequence IDs low to high, then check for missing sequences.
        sort($res, SORT_NUMERIC);
        foreach ($res as $key => $value) {
            msg("    Checking: $key, $value");
            # Since sequence IDs always start at 0, any mismatch between the
            # key and value of the array indicates an uncompleted task.
            if ($key != $value) {
                msg("    Next sequence ID for $assignment: " . $key + 1);
                # Increment the current key and assign that as the next task.
                return $key + 1;
            }
        }
        msg("    Next sequence ID for $assignment: " . count($res));
        # Otherwise, return the number of completed tasks as that will be the
        # offset of the next sequence ID.
        return count($res);
    }

    # Fetches the plan for a given assignment.
    function get_plan($assignment) {
        $res = $this->exec1('SELECT list FROM plan WHERE assignmentId=?', array($assignment), PDO::FETCH_NUM);
        $res = array_shift($res); # should be only one plan
        return json_decode($res[0]);
    }

    function get_assignment_info($assignment) {
        $res = $this->exec1('SELECT workerId, hitId, turkSubmitTo FROM plan WHERE assignmentId = ?', array($assignment), PDO::FETCH_NUM);
        return (array) array_shift($res);
    }

    function get_all_results($assignment) {
        $plan = $this->get_plan($assignment);
        $res = $this->exec1('SELECT sequence, result FROM log WHERE assignmentId = ? AND type="completed"', array($assignment), PDO::FETCH_ASSOC);
        $ret = array();
        foreach ($res as $key => $value) {
            $ret[] = array(
                "sequence" => $value['sequence'],
                "result" => $value['result'],
                "plan" => $plan[$value['sequence']]
            );
        }
        return json_encode($ret);
    }
}

?>
