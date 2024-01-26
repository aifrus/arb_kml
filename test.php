<?php

class KMLGenerator
{
    public function __construct(private $sql, private $kmlDirectory)
    {
        if (!is_dir($this->kmlDirectory)) {
            mkdir($this->kmlDirectory, 0777, true);
        }
    }

    private function generateKMLContent($data, $boundaryData)
    {
        $description = <<<HTML
    <b>Effective Date:</b> {$data['EFF_DATE']}<br/>
    <b>Location ID:</b> {$data['LOCATION_ID']}<br/>
    <b>Altitude:</b> {$data['ALTITUDE']}<br/>
    <b>Type:</b> {$data['TYPE']}<br/>
    <b>Boundary Point Description:</b> {$data['BNDRY_PT_DESCRIP']}<br/>
    <b>NAS Description Flag:</b> {$data['NAS_DESCRIP_FLAG']}
    HTML;

        $coordinates = '';
        foreach ($boundaryData as $point) {
            $coordinates .= "{$point['LONG_DECIMAL']},{$point['LAT_DECIMAL']} ";
        }

        $kmlContent = <<<KML
    <?xml version="1.0" encoding="UTF-8"?>
    <kml xmlns="http://www.opengis.net/kml/2.2">
    <Placemark>
        <name>{$data['LOCATION_NAME']}</name>
        <description><![CDATA[{$description}]]></description>
        <Polygon>
            <outerBoundaryIs>
                <LinearRing>
                    <coordinates>
                    {$coordinates}
                    </coordinates>
                </LinearRing>
            </outerBoundaryIs>
        </Polygon>
    </Placemark>
    </kml>
    KML;

        return $kmlContent;
    }

    public function generateAndSaveKMLFiles()
    {
        $result = $this->sql->query("SELECT * FROM `ARB_BASE`");
        while ($row = $result->fetch_assoc()) {
            $boundaryData = $this->sql->query("SELECT * FROM `ARB_SEG` WHERE `LOCATION_ID` = '{$row['LOCATION_ID']}' ORDER BY `POINT_SEQ`")->fetch_all(MYSQLI_ASSOC);
            file_put_contents($this->kmlDirectory . $row['LOCATION_ID'] . '.kml', $this->generateKMLContent($row, $boundaryData));
        }
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

$sql = mysqli_connect('127.0.0.1', 'aifr', 'aifr', 'NASR_2024-01-25');
$kmlGenerator = new KMLGenerator($sql, __DIR__ . '/kml_out/');
$kmlGenerator->generateAndSaveKMLFiles();
$kmlGenerator->zipKMLFiles();
