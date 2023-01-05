<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWR DATA DEFAULT</title>
</head>
<body>
    <?php
        // --- Require DB connection
        require_once("conn.php");

        // --- Array lists to run
        $array_projects = array(
            array("byravn.dk", "byravn.dk"),
            array("byravn.se", "byravn.se"),
            array("byravn.no", "byravn.no/"),
            array("vestermarkribe.dk", "vestermarkribe.dk/")
        );
        $token = "1e4a1d8dc5cf80f19b468f28f640e841";

        foreach ($array_projects as $key) {
            // - Variables
            $project_name = $key[0];
            $project_name_sanitized = preg_replace("/[\W_]+/u", '', $project_name);
            $project_website = $key[1];

            // - Check if database table already exists
            $table_suffix = $project_name_sanitized;

            if (mysqli_query($con, "DESCRIBE `awr_data_default_".$table_suffix."`")) {
                // - Table exists, empty table
                mysqli_query($con, "TRUNCATE TABLE `awr_data_default_".$table_suffix."`");
                echo "<script>console.log('SQL table already exists (awr_data_default_".$table_suffix."). - table emptied')</script>";
                return;
            } else {
                // - Table does not exists, create it
                $sql_create_table = "CREATE TABLE `awr_data_default_".$table_suffix."` (
                    unique_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    date VARCHAR(255) NOT NULL,
                    category VARCHAR(255) NOT NULL,
                    subcategory VARCHAR(255) NOT NULL,
                    search_engine VARCHAR(255) NOT NULL,
                    keyword VARCHAR(255) NOT NULL,
                    keyword_group VARCHAR(255) NOT NULL,
                    website VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    position INT(11) NOT NULL,
                    best VARCHAR(255) NOT NULL,
                    competition VARCHAR(255) NOT NULL,
                    average_monthly_searches INT(11) NOT NULL,
                    cpc VARCHAR(255) NOT NULL,
                    page VARCHAR(255) NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    local_searches VARCHAR(255) NOT NULL,
                    estimated_daily_traffic VARCHAR(255) NOT NULL,
                    project_client VARCHAR(255) NOT NULL,
                    priority INT(11) NOT NULL,
                    search_intent VARCHAR(255) NOT NULL
                )";

                if ($con->query($sql_create_table) === TRUE) {
                    echo "<script>console.log('SQL table created successfully (awr_data_default_".$table_suffix.").')</script>";
                } else {
                    echo "ErrorResponse: Error creating table: " . $con->error;
                }
            }

            // - First empty the "exported_data_csv" folder & zip folder.
            try {
                array_map( 'unlink', array_filter((array) glob("exported_data_csv/*") ) );
                array_map( 'unlink', array_filter((array) glob("zip_file/*") ) );
            } catch (\Throwable $th) {
                throw $th;
            } finally {
                // - Get dates
                $url_get_dates = "https://api.awrcloud.com/v2/get.php?action=get_dates&project=".$project_name."&token=".$token."";
                $response_get_dates = json_decode(file_get_contents($url_get_dates), true);
                $project_date = $response_get_dates["details"]["dates"][0]["date"];

                // - Export Ranking
                $url = "https://api.awrcloud.com/v2/get.php?action=export_ranking&project=".$project_name."&token=".$token."&startDate=".$project_date."&stopDate=2022-12-12";
                $response = json_decode(file_get_contents($url), true);

                if ($response["response_code"] === 10 || $response["response_code"] === 0) {
                    // - Project has already been exported OR has not been exported yet
                    $csv_link = $response["details"];

                    $f = file_put_contents("zip_file/my-zip.zip", fopen($csv_link, 'r'), LOCK_EX);
                    if(FALSE === $f) {
                        echo "ErrorResponse: Couldn't write to file.";
                    }

                    $zip = new ZipArchive;
                    $res = $zip->open('zip_file/my-zip.zip');
                    if ($res === TRUE) {
                        $zip->extractTo('exported_data_csv');
                        $zip->close();
                        echo "<script>console.log('.zip extracted successfully.')</script>";

                    } else {
                        echo "ErrorResponse: .zip results couldn't be extracted.";
                    }
                }

                $files = glob('exported_data_csv/*csv');
                $iterator_files = 0;
                foreach($files as $file) {
                    $CSV_fopen = fopen($file, "r");
                    
                    if ($CSV_fopen !== FALSE) {
                        while (!feof($CSV_fopen)) {
                            $data = fgetcsv($CSV_fopen);

                            if (!empty($data)) {
                                // - CSV data exists, insert into database table
                                if ($iterator_files > 0) {
                                    $table = "awr_data_default_".$project_name_sanitized;
                                    
                                    // - Check if average_monthly_searches is > 0
                                    if ($data[4] === $project_website) {
                                        $sql_insert = "INSERT INTO ".$table." (date, search_engine, keyword, keyword_group, website, url, position, best, competition, average_monthly_searches, cpc, page, type, local_searches, estimated_daily_traffic, project_client)
                                        VALUES ('".mysqli_real_escape_string($con, $data[0])."', '".mysqli_real_escape_string($con, $data[1])."', '".mysqli_real_escape_string($con, $data[2])."', '".mysqli_real_escape_string($con, $data[3])."', '".mysqli_real_escape_string($con, $data[4])."', '".mysqli_real_escape_string($con, $data[5])."', '".mysqli_real_escape_string($con, $data[6])."', '".mysqli_real_escape_string($con, $data[7])."', '".mysqli_real_escape_string($con, $data[8])."', '".mysqli_real_escape_string($con, $data[9])."', '".mysqli_real_escape_string($con, $data[10])."', '".mysqli_real_escape_string($con, $data[11])."', '".mysqli_real_escape_string($con, $data[12])."', '".mysqli_real_escape_string($con, $data[14])."', '".mysqli_real_escape_string($con, $data[15])."', '".mysqli_real_escape_string($con, $project_name_sanitized)."')";

                                        if ($con->query($sql_insert) === TRUE) {
                                        
                                        } else {
                                            echo "Error: " . $sql_insert . "<br>" . $con->error;
                                        }
                                    }
                                }
                            }

                            $iterator_files++;
                        }
                    } else {
                        echo "ErrorReponse: Couldn't open/read CSV file.";
                    }
                }
            }
        }
    ?>
</body>
</html>