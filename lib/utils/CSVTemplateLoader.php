<?php

class CSVTemplateLoader
{
    protected $zipfile = "";
    protected $zip = NULL;
    protected $temp_filename = "";
    protected $currentRow = -1;

    protected $errors = array();
    protected $notices = array();

    protected $success_rows = 0;
    protected $error_rows = 0;

    public function getSuccessRowCount()
    {
        return $this->success_rows;
    }

    public function getErrorRowCount()
    {
        return $this->error_rows;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    public function __construct($zipfile)
    {
        if (strlen($zipfile) < 1) throw new Exception("No file to import specified");

        $this->zipfile = $zipfile;
        $this->zip = new ZipArchive;

        $res = $this->zip->open($this->zipfile);

        if ($res !== TRUE) {
            throw new Exception("ZipOpen failed, code:$res");
        }
        $this->temp_filename = "/tmp/template_loader-" . time() . "-" . rand() . ".tmp";
    }

    public function __destruct()
    {
        $this->zip->close();
        @unlink($this->temp_filename);
    }

    // 	'template.csv'
    public function processFile($csvfile)
    {
        $ret_tmp = $this->zip->statName($csvfile);
        if ($ret_tmp === FALSE) {
            throw new Exception("'$csvfile' not found inside zip archive");
        }

        //unarchive in a temp folder
        file_put_contents($this->temp_filename, $this->zip->getFromName($csvfile));

        $handle = fopen($this->temp_filename, 'r');

        $fields = array();
        $fkeys = array();

        if (!$handle) {

            throw new Exception("Error loading bundle. Could not open template temp file: {$this->temp_filename}");
        }

        $this->currentRow = 1;

        $this->startLoad();

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($data);
            $this->errors[$this->currentRow] = "";
            $this->notices[$this->currentRow] = "";
            if ($this->currentRow === 1) {
                $this->processKeysRow($data);
            }
            else {
                try {
                    $this->processDataRow($data);
                    $this->success_rows++;
                }
                catch (Exception $e) {
                    $this->error_rows++;
                    $this->errors[$this->currentRow] = $e->getMessage();

                }
            }
            $this->currentRow++;
        }
        $this->finishLoad();
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function startLoad()
    {
        echo "<table class='csv_template_loader'>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function finishLoad()
    {
        echo "</table>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function processKeysRow($row)
    {
        echo "<tr>";
        foreach ($row as $key => $val) {
            echo "<th>$val</th>";
        }
        echo "</tr>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function processDataRow($row)
    {

        echo "<tr>";
        foreach ($row as $key => $val) {
            echo "<td>$val</td>";
        }
        echo "</tr>";
    }
}

?>