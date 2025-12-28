<?php
class dbcontroller
{
    private $host = '127.0.0.1';
    private $user = 'root';
    private $password = '';
    private $database = 'rumahsakit';
    private $koneksi;

    public function __construct()
    {
        $this->koneksi = $this->koneksiDB();
        if (mysqli_connect_errno()) {
            die("Koneksi database gagal: " . mysqli_connect_error());
        }
    }

    private function koneksiDB()
    {
        return mysqli_connect($this->host, $this->user, $this->password, $this->database);
    }

    public function execute($sql, $types = "", $params = [])
    {
        if ($types === "" || empty($params)) {
            $result = mysqli_query($this->koneksi, $sql);

            if (stripos(trim($sql), "SELECT") === 0) {
                $data = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $data[] = $row;
                    }
                }
                return $data;
            }

            return $result ? true : false;
        }

        $stmt = mysqli_prepare($this->koneksi, $sql);

        if ($stmt === false) {
            error_log("Prepare Error: " . mysqli_error($this->koneksi));
            return false;
        }
        $bind = [];
        $bind[] = $types;

        foreach ($params as $key => $value) {
            $bind[] = &$params[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bind);

        $exec = mysqli_stmt_execute($stmt);

        if (!$exec) {
            error_log("Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }

        if (stripos(trim($sql), "SELECT") === 0) {
            $result = mysqli_stmt_get_result($stmt);
            $data = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
            return $data;
        }

        mysqli_stmt_close($stmt);
        return true;
    }

    public function getALL($sql)
    {
        $result = mysqli_query($this->koneksi, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getITEM($sql)
    {
        $result = mysqli_query($this->koneksi, $sql);
        return $result ? mysqli_fetch_assoc($result) : false;
    }

    public function rowCOUNT($sql)
    {
        $result = mysqli_query($this->koneksi, $sql);
        return $result ? mysqli_num_rows($result) : 0;
    }

    public function runSQL($sql)
    {
        return mysqli_query($this->koneksi, $sql);
    }

    public function runSimpleQuery($sql)
    {
    if (!$this->koneksi) {
        die("Koneksi database belum diinisialisasi.");
    }
    
    $result = mysqli_query($this->koneksi, $sql);
    
    if (!$result) {
        error_log("Query Error: " . mysqli_error($this->koneksi));
    }
    
    return $result; 
    }

    public function getLIST($sql)
    {
    return $this->getALL($sql);
    }

    public function escapeString($string)
    {
    return mysqli_real_escape_string($this->koneksi, $string);
    }

    public function runQueryWithParams($query, $param_type, $param_value_array) {
    global $conn;

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $params = array_merge([$param_type], $param_value_array);
    call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params));
    $stmt->execute();
    
    if ($stmt->error) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

    private function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;

    }


}
