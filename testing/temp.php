<?php
    $CSVfp = fopen("fruits.csv", "r");
    if ($CSVfp !== FALSE) {
        ?>
        <div class="phppot-container">
            <table class="striped">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>COLOR</th>
                    </tr>
                </thead>
    <?php
        while (! feof($CSVfp)) {
            $data = fgetcsv($CSVfp, 1000, ",");
            if (! empty($data)) {
                ?>
                <tr class="data">
                    <td><?php echo $data[0]; ?></td>
                    <td><div class="property-display"
                            style="background-color: <?php echo $data[2]?>;"><?php echo $data[1]; ?></div></td>
                </tr>
    <?php }?>
    <?php
        }
        ?>
            </table>
        </div>
    <?php
    }
    fclose($CSVfp);
?>