<?php
require_once('BurndownChart.php');
$chart = new BurndownChart();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BurndownChart</title>
    <link href="vendor/twitter/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/datepicker/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="https://github.highcharts.com/gantt/highcharts-gantt.src.js"></script>

    <script src="vendor/twitter/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="assets/datepicker/bootstrap-datepicker.min.js"></script>
<!--    <script src="node_modules/highcharts/highcharts.js"></script>-->
    <script src="node_modules/moment/min/moment.min.js"></script>
<!--    <script src="https://github.highcharts.com/gantt/highcharts-gantt.src.js"></script>-->

    <script src="assets/script.js"></script>
</head>
<body>
    <div class="preload">
        <div class="windows8">
            <div class="wBall" id="wBall_1">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_2">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_3">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_4">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_5">
                <div class="wInnerBall"></div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel">

                    <div class="input-group date date-filter">
                        <div class="date_filter">
                            <label for="inputSTDate">Select starting date:</label>
                            <input type="text" name="inputSTDate" id="inputSTDate" class="form-control datepicker">
                        </div>
                        <div class="date_filter">
                            <label for="inputENDate">Select end date:</label>
                            <input type="text" name="inputENDate" id="inputENDate" class="form-control datepicker">
                        </div>
                        <button class="btn btn-default submit date_filter">Filter</button>
                        <button class="btn btn-default date_filter clrbtn ">Clear dates</button>
                    </div>

                    <div id="branches_div">
                        <div class="branch_select">
                            <label for="br" class="br_in">Select branch</label>
                            <select  class="form-control download_select"  name="br" id="branches"></select>
                        </div>

                        <button    class="btn btn-default download_btn"  id="download">Download</button>
                    </div>

                    <div class="form-group">
                        <div class="container-sel-project">
                            <label for="sel-projects">Select project:</label>
                            <select class="form-control" id="sel-projects">
                            <?php
                                foreach ($chart->get_all_project() as $item){
                                    echo '<option data-id="'.$item['id'].'">'.$item['name'].'</option>';
                                }
                            ?>
                            </select>
                        </div><!--

                        --><div class="container-sel-milestones">
                            <label for="sel-milestones">Select milestone:</label>
                            <select class="form-control" id="sel-milestones">
                            </select>
                        </div>
                    </div>



                    <div class="select-container">
                        <label for="select-container-list">Select chart style</label>
                        <select id="select-container-list" class="form-control container-list" >
                            <option value="1">Burndown Chart</option>
                            <option value="2">Horizontal Chart</option>
                        </select>
                    </div>

                    <div class="container-all">
                        <div class="preload2">
                            <div class="windows8">
                                <div class="wBall" id="wBall_1">
                                    <div class="wInnerBall"></div>
                                </div>
                                <div class="wBall" id="wBall_2">
                                    <div class="wInnerBall"></div>
                                </div>
                                <div class="wBall" id="wBall_3">
                                    <div class="wInnerBall"></div>
                                </div>
                                <div class="wBall" id="wBall_4">
                                    <div class="wInnerBall"></div>
                                </div>
                                <div class="wBall" id="wBall_5">
                                    <div class="wInnerBall"></div>
                                </div>
                            </div>
                        </div>
                        <div id="container-burndown">
                            <div class="clearfix"></div>
                        </div>
                    </div>


                    <div id="button">
                        <label id="check_label" for="check_list">Show open issues</label>
                        <input type="checkbox" disabled="true" name="ch_li" id="check_list">
                    </div>

                    <div id="open_list"></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

