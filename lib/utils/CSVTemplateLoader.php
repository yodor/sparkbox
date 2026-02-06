<?php

class CSVTemplateLoader
{
    protected string $zipfile = "";
    protected ZipArchive $zip;
    protected string $temp_filename = "";
    protected int $currentRow = -1;

    protected array $errors = array();
    protected array $notices = array();

    protected int $success_rows = 0;
    protected int $error_rows = 0;

    public function getSuccessRowCount() : int
    {
        return $this->success_rows;
    }

    public function getErrorRowCount() : int
    {
        return $this->error_rows;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function getNotices() : array
    {
        return $this->notices;
    }

    public function __construct(string $zipfile)
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
    public function processFile(string $csvfile) : void
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

            throw new Exception("Error loading bundle. Could not open template temp file: $this->temp_filename");
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
    public function startLoad() : void
    {
        echo "<table class='csv_template_loader'>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function finishLoad() : void
    {
        echo "</table>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function processKeysRow(array $row) : void
    {
        echo "<tr>";
        foreach ($row as $key => $val) {
            echo "<th>$val</th>";
        }
        echo "</tr>";
    }

    //dummy output only function. actual functionality reimplemented in a subclass
    public function processDataRow(array $row) : void
    {

        echo "<tr>";
        foreach ($row as $key => $val) {
            echo "<td>$val</td>";
        }
        echo "</tr>";
    }
}

?>
