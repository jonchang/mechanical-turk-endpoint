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
            $this->exec('PRAGMA foreign_keys = ON;');
            $this->exec('PRAGMA journal_mode = wal;');
            $this->beginTransaction();
            $this->exec('CREATE TABLE IF NOT EXISTS hits (hitId TEXT PRIMARY KEY, count INTEGER NOT NULL, endpoint TEXT NOT NULL, parameters BLOB);');
            $this->exec('CREATE TABLE IF NOT EXISTS assignments (assignmentId TEXT PRIMARY KEY, hitId TEXT NOT NULL, workerId TEXT NOT NULL, assigned_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(hitId) REFERENCES hits(hitId));');
            $this->exec('CREATE TABLE IF NOT EXISTS results (assignmentId TEXT NOT NULL, completed INTEGER, completed_at TEXT DEFAULT CURRENT_TIMESTAMP, result BLOB, FOREIGN KEY(assignmentId) REFERENCES assignments(assignmentId));');
            $this->commit();
        } catch (PDOException $e) {
            $this->rollBack();
            die('Transaction failed: ' . $e->getMessage());
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

    # Adds a HIT to the hit table
    function add_hit($hit, $count, $endpoint, $data) {
        msg("adding plan: $hit x $count @ $endpoint");
        $this->exec1(
            'INSERT INTO hits(hitId, count, endpoint, parameters) VALUES(?,?,?,?);',
            array($hit, $count, $endpoint, json_encode($data))
        );
    }

    # For a given worker ID, tries to find assignments that haven't been
    # completed for that worker
    function get_incomplete_assignment($worker) {
        $res = $this->exec1(
            'SELECT assignments.assignmentId, assignments.hitId, assignments.assigned_at FROM assignments LEFT JOIN results USING (assignmentId) WHERE completed IS NULL LIMIT 1;',
            array(), PDO::FETCH_NUM
        );
        if (empty($res)) {
            return $res;
        }
        $res = array_shift($res); # should only be one result
        return array(hit => $res[1], assignment => $res[0], timestamp => $res[2]);
    }

    # Fetches a random HIT that hasn't been completed and generates an
    # assignment ID based on the worker. Returns the HIT and assignment
    # IDs as an associative array.
    function assign_work($worker) {
        $res = $this->get_incomplete_assignment($worker);
        if (!empty($res)) {
            msg('found incomplete assignment ' . $assignment);
            return array(hit => $res['hit'], assignment => $res['assignment']);
        }
        $res = $this->exec1(
            'SELECT hitId, COUNT(results.assignmentId) AS cur_count FROM hits LEFT JOIN assignments USING (hitId) INNER JOIN results USING (assignmentId) GROUP BY hitId HAVING cur_count < hits.count AND hitId NOT IN (SELECT hitId FROM assignments WHERE workerId = ?) ORDER BY cur_count, RANDOM() LIMIT 5;',
            array($worker), PDO::FETCH_KEY_PAIR);
        if (empty($res)) {
            # No valid hits left to complete!
            return array();
        }
        $potential_hits = array_keys($res);
        shuffle($potential_hits);
        $hit = array_shift($potential_hits); # gets the first key
        $assignment = sha1($worker . $hit);
        $this->exec1(
            'INSERT INTO assignments (assignmentId, hitId, workerId) VALUES (?,?,?);',
            array($assignment, $hit, $worker)
        );
        return array(hit => $hit, assignment => $assignment);
    }

    function get_hit_info($hit) {
        $res = $this->exec1('SELECT count, endpoint, parameters FROM hits WHERE hitId = ?', array($hit), PDO::FETCH_ASSOC);
        $res = array_shift($res);
        $res['parameters'] = json_decode($res['parameters'], true);
        return $res;
    }

    function get_assigned_worker($assignment) {
        $res = $this->exec1('SELECT workerId from assignments WHERE assignmentId = ?', array($assignment), PDO::FETCH_COLUMN);
        return array_shift($res);
    }

    # Gets the number of hits in the table
    function count_hits() {
        $res = $this->exec1('SELECT COUNT(1) as count, COUNT(DISTINCT parameters) FROM hits;', array(), PDO::FETCH_NUM);
        return array_shift($res);
    }

    # Appends a result to the log table.
    function add_result($assignment, $result) {
        msg("adding result $assignment");
        $this->exec1(
            'INSERT INTO results(assignmentId, completed, result) VALUES(?,?,?);',
            array($assignment, 1, $result)
        );
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
