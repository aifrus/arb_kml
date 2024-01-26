<?php

class KMLGenerator
{
    public function __construct(private $sql, private $kmlDirectory)
    {
        if (!is_dir($this->kmlDirectory)) {
            mkdir($this->kmlDirectory, 0777, true);
        }
    }

    private function generateKMLContent($data)
    {
        $description = <<<HTML
    <b>Effective Date:</b> {$data['EFF_DATE']}<br/>
    <b>Location ID:</b> {$data['LOCATION_ID']}<br/>
    <b>Computer ID:</b> {$data['COMPUTER_ID']}<br/>
    <b>ICAO ID:</b> {$data['ICAO_ID']}<br/>
    <b>City:</b> {$data['CITY']}<br/>
    <b>State:</b> {$data['STATE']}<br/>
    <b>Country Code:</b> {$data['COUNTRY_CODE']}<br/>
    <b>Cross Reference:</b> {$data['CROSS_REF']}
    HTML;

        $kmlContent = <<<KML
    <?xml version="1.0" encoding="UTF-8"?>
    <kml xmlns="http://www.opengis.net/kml/2.2">
    <Placemark>
        <name>{$data['LOCATION_NAME']}</name>
        <description><![CDATA[{$description}]]></description>
        <Point>
            <coordinates>{$data['LONG_DECIMAL']},{$data['LAT_DECIMAL']}</coordinates>
        </Point>
    </Placemark>
    </kml>
    KML;

        return $kmlContent;
    }
    public function generateAndSaveKMLFiles()
    {
        foreach ($this->sql->query("SELECT * FROM `ARB_BASE`")->fetch_all(MYSQLI_ASSOC) as $row) file_put_contents($this->kmlDirectory . $row['LOCATION_ID'] . '.kml', $this->generateKMLContent($row));
    }

    public function zipKMLFiles()
    {
        $zip = new ZipArchive();
        $zipFilename = 'kml_files.zip';

        if ($zip->open($this->kmlDirectory . $zipFilename, ZipArchive::CREATE) === TRUE) {
            $files = glob($this->kmlDirectory . '*.kml');
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            echo "KML files zipped successfully!";
        } else {
            echo "Failed to create zip file.";
        }
    }
}

$pdo = mysqli_connect('127.0.0.1', 'aifr', 'aifr', 'NASR_2024-01-25');
$kmlGenerator = new KMLGenerator($sql, __DIR__ . '/kml_out/');
$kmlGenerator->generateAndSaveKMLFiles();
$kmlGenerator->zipKMLFiles();
