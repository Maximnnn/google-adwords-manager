<?php namespace App\Helpers;

class Csv {

    /**
     * @param $data array ('fields' => $fields, 'rows' => $rows, 'filename' => 'filename.csv')
     *  $fields array (row_key => csv_field_name)
     *  $rows   array (row_key => value)
     *  $filename
     * @throws \Exception
     * @return string csv format
     */
    public static function createAndReturnCsv($data){

        if (!isset($data['excel_rows']) or !is_array($data['excel_rows']) or !isset($data['excel_fields']) or !is_array($data['excel_fields'])){
            throw new \Exception('response data doesn`t contain "excel_rows" or "excel_fields"');
        }
        $C = new Csv;
        $csv = $C->toCSV($data['excel_fields'], $data['excel_rows']);
        $out = fopen('php://output', 'w');
        foreach ($csv as $row)
            fputcsv($out, $row,",",'"');
        fclose($out);
        return;
    }

    public function toCSV($fields, $rows){
        $csvrows = array();
        $i=0;
        foreach ($fields as $row) {
            $csvrows[0][] = $row;
        }
        if (isset($rows))
            foreach($rows as $row){
                $i++;
                foreach ($fields as $key => $value){
                    if (isset($row[$key]))
                        $csvrows[$i][] = trim(preg_replace('/\s\s+/', ' ', strip_tags(str_replace(',',' ', $row[$key]))));
                    else
                        $csvrows[$i][]="";
                }
            }
        return $csvrows;
    }

}