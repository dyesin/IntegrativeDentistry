<?php
$link = mysql_connect('localhost', 'root', '');
mysql_select_db('freedental');
$query = "select
	YEAR (apt.AptDateTime) as year,
	MONTH(apt.AptDateTime) as month,
	count(*) as count
    from appointment apt, patient pts
    where apt.PatNum = pts.patNum and
    apt.Op <> 3 AND
    apt.ProvNum <> 6 AND
    apt.IsNewPatient = 1 AND
    HOUR(apt.AptDateTime) BETWEEN 8 AND 18 and
    YEAR(apt.AptDateTime)  >= YEAR(DATE_SUB(CURDATE(), INTERVAL 4 YEAR)) AND
    apt.AptDateTime < curdate()
    GROUP BY month, year
    order by month, year;";

$result = mysql_query($query);

if (!$result) {
    $message = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

while ($row = mysql_fetch_assoc($result)) {
    $patients[$row['ProvNum']][] = $row;
}

$current_year = date("Y");
$last_year = date("Y", strtotime("-1 year"));

foreach ($patients as $p => $v) {
    for ($i = 0; $i <= sizeof($v); $i++) {
        if ($v[$i]['year'] == $current_year || $v[$i]['year'] == $last_year) {
            $pat [] = $v[$i];
        }
    }
}
?>
<html>
<head>
    <title>New Patients Performance</title>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Year', '<?=$last_year;?>', '<?=$current_year;?>'],
            <?
            $last_year = '';
            for ($i = 0; $i <= sizeof($pat); $i++) {
                if ($pat[$i]['year'] == $last_year || $last_year == '') {
                    echo "[";
                    $month = $data[$i]['month'];
                    echo "'" . date("M", mktime(0, 0, 0, $month)) . "', ";
                    echo       $pat[$i]['count'] . ", ";
                    if (($pat[$i]['year'] != $pat[$i + 1]['year']) && $pat[$i + 1]['year'] != '') {
                        echo       $pat[$i + 1]['count'] . "],";
                    } else {
                        echo       '0' . "],";
                    }
                    echo "\n";
                    $last_year = $pat[$i]['year'];
                }
            }
            ?>
            ]);

            var options = {
                title: 'New Patients Performance',
                hAxis: {title: '', titleTextStyle: {color: 'red'}}
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>

    <style>
        body {
            font-family: verdana, arial, sans-serif;
            font-size: 13px;
            color: #000;
        }

        div {
            padding-bottom: 15px;
        }

        span {
            color: #FF0000;
            font-weight: bold;
        }

        table.imagetable {
            font-size: 11px;
            border-width: 1px;
            border-color: #999999;
            border-collapse: collapse;
            width: 550px;
        }

        table.imagetable td {
            border-width: 1px;
            text-align: center;
            padding: 5px;
            border-style: solid;
            border-color: #999999;
        }

        table.imagetable tr:nth-child(odd) {
            background-color: #fff;
        }

        table.imagetable tr:nth-child(even) {
            background-color: #eee;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div id="chart_div" style="width: 1000px; height: 450px; margin-bottom: 0; padding-bottom: 0;"></div>
<?
foreach ($patients as $provider_id => $data) {
    foreach ($data as $key => $value) {
        $arr[$value['year']][$value['month']] = $value['count'];
        $totals[$value['year']] += $value['count'];
    }
}
?>
<div style="width: 1000px; ">
    <table class='imagetable' style="width: 500px;" align="center">
        <tr>
            <td></td>
            <?
            for ($i = 1; $i <= 12; $i++) {
                echo "<td>" . date("M", mktime(0, 0, 0, $i)) . "</td>";
                if ($i == 12) {
                    echo "<td>TOTAL</td>";
                }
            }
            echo "</tr>";

            foreach ($arr as $data => $year) {
                $count = 0;
                echo "<tr>";
                echo "<td class='bold'>" . $data . "</td>";
                foreach ($year as $key => $value) {
                    echo "<td>" . $value . "</td>";
                    $count++;
                }
                $offset = 13 - $count;
                if ($offset > 0) {
                    for ($i = 1; $i <= $offset; $i++) {
                        if ($i == $offset) {
                            echo "<td class='bold'>" . $totals[$data] . "</td>";
                        }
                        else {
                            echo "<td></td>";
                        }
                    }
                    $offset = 0;
                }

                echo "</tr>";
            }
            echo "</table></div>";

            if (!is_array($patients)) {
                print "No new patients";
                exit;
            }
            mysql_close($link);
            ?>

</body>
</html>
