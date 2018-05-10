(function ($) {
    "use strict";

    $(document).ready(function () {
        var chart;
        buildChart([], []);

        function buildChart(idealData, actualData){
            // console.log(idealData)
            // console.log(actualData)
            chart = $('#container-burndown').highcharts({
                title: {
                    text: 'Burndown Chart',
                    x: -10 //center
                },
                chart: {
                    zoomType: 'x'
                },
                scrollbar: {
                    barBackgroundColor: 'gray',
                    barBorderRadius: 7,
                    barBorderWidth: 0,
                    buttonBackgroundColor: 'gray',
                    buttonBorderWidth: 0,
                    buttonBorderRadius: 7,
                    trackBackgroundColor: 'none',
                    trackBorderWidth: 1,
                    trackBorderRadius: 8,
                    trackBorderColor: '#CCC'
                },
                colors: ['blue', 'red'],
                plotOptions: {
                    line: {
                        lineWidth: 3,
                        marker:{
                            enabled: true
                        }
                    },
                    tooltip: {
                        hideDelay: 200
                    }
                },
                subtitle: {
                    text: 'For GitLab Projects',
                    x: -10
                },
                xAxis: {
                    type: 'datetime',
                    labels: {
                        formatter: function() {
                            return moment(this.value).format("MMM D YYYY");
                        }
                    },
                    tickInterval: moment.duration(1, 'days').asMilliseconds()
                },
                yAxis: {
                    title: {
                        text: 'Issues quantity'
                    },
                    min:0,
                    tickInterval :1

                },

                tooltip: {
                    valueSuffix: ' day',
                    crosshairs: true,
                    shared: true,
                    formatter: function() {
                        var result =  '<b>' + Highcharts.dateFormat('%A, %b %e, %H:%M:%S', this.x) + '</b>';
                        $.each(this.points, function(i, datum) {
                            var lastDate = idealData[idealData.length - 1];
                            if(datum.x > (lastDate.date * 1000)) {
                                result += '<br /><span style="color: rgba(255,0,0,0.25)">●</span> Ideal burn: 0 day<br><span style="color: '+ this.series.color + '">●</span> Actual burn: ' + datum.y + ' day';
                            }
                            else {
                                result += '<br /><span style="color:' + this.series.color + '">●</span> ' + this.series.name + ': ' + datum.y + ' day';
                            }
                        });
                        return result;
                    }
                },
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom',
                    borderWidth: 0
                },
                series: [
                {
                    name: 'Ideal Burn',
                    type: 'line',
                    color: 'rgba(255,0,0,0.25)',
                    lineWidth: 2,
                    dashStyle: "Dash",
                    data: prepareData(idealData)
                },
                {
                    name: 'Actual Burn',
                    type: 'line',
                    color: 'rgba(0,120,200,0.75)',
                    marker: {
                        radius: 6
                    },
                    data: prepareData(actualData)
                }]
            });
        }





        var chart2;
        function buildSecondChart(hr, minDate, maxDate){
            console.log(minDate, maxDate)
            chart2 = Highcharts.ganttChart('container-burndown', {
                title: {
                    text: 'Milestones in timeline',
                    x: -10 //center
                },
                chart: {
                     zoomType: 'x'
                },
                yAxis: {
                    // min:minDate,
                    // max:maxDate
                    labels: {
                        style:{
                            width:'250px'
                        },
                        step: 1
                    },
                    alignTicks: false
                },
                series: [
                        {
                            data:buildArray(hr)

                        }
                    ]
                });

            debugger;
            if(chart2.yAxis && chart2.yAxis[0].ticks){
                Object.keys(chart2.yAxis[0].ticks).map(function(i){
                    var tick = chart2.yAxis[0].ticks[i];
                    if(tick && typeof tick !== 'undefined' && typeof tick.collapse === 'function' && tick.label && tick.label.treeIcon){
                        tick.collapse(false);
                    }
                });

                chart2.redraw();
            }
        }



        function buildArray(arr){
            var currentMilestone = $('#sel-milestones').children('option').filter(':selected');
            var asd = [];
            if (typeof arr != 'undefined'){
                for(var i = 0; i < arr.length; i++) {
                    if (currentMilestone.val() == 'all_milestones') {
                        asd.push(arr[i]);
                    }
                    else if (arr[i].parent == currentMilestone.val() && arr[i].length != 0) {
                        delete arr[i].parent;
                        asd.push(arr[i]);
                    }
                }
            }
            return asd;
        }







        function prepareData(data){
            if (data.length) {
                var tmp = [];
                $.each(data, function(key, item){
                    tmp.push([moment.utc(item.date * 1000).valueOf(), item.items]);
                });

                return tmp.sort(function(a,b){
                    return a[0] - b[0];
                });
            }
        }

        $('#sel-projects').change(function (e) {
            var currentProject = $(this).children('option').filter(':selected').text();
            var currentID = $(this).children('option').filter(':selected').data('id');
            $.post('index.php?action=select_project',{id: currentID, name:  currentProject},  function (resp) {
                if(resp){
                    $('#sel-milestones').empty();
                    $('#sel-milestones').append($('<option value="all_milestones">All milestones</option><option value="all_issues">All issues</option>'));
                    if(resp.length > 0){
                        $.each(resp, function (key, value) {
                            $('#sel-milestones').append($('<option value="'+value.id+'" data-date="'+value.created_at+'">'+value.name+'</option>'));
                        });
                    }

                    $('#sel-milestones').change();
                }
            }, 'json');
        });
        $('#sel-projects').trigger('change');

        function show_list(list){
            $("#open_list").empty();
            $.each(list, function(i, v){
                var currentProject = $('#sel-projects').children('option').filter(':selected').text();
                var mom = moment(list[i].updated_at);
                var mom_n = mom.format("DD/MM HH:mm");
                var creat = moment(list[i].updated_at, "YYYY-MM-DD").valueOf();


                $('#open_list').append("<a class='list_class' target='_blank' data_time='"+ creat +"' href='http://gitlab.simplyhq.com/Dubbi/"+ currentProject +"/issues/"+ list[i].iid + "'><div><b>" + list[i].title + "</b> "
                    + "(Updated " + mom_n  + ") <br>"
                    + "# " + list[i].iid + " - "
                    + "Assigned to " + (list[i].assignee ? list[i].assignee.name : "-") + " - "
                    +  ((list[i].labels.length !=0) ? "\""+list[i].labels+"\"" : "no labels") + "<br>" +
                    "</div><br></a>");
            });
        }

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        }).on('changeDate', function(e){
            $(this).datepicker('hide');
        });





        $('#sel-milestones').change(function (e) {
            var projectID = $('#sel-projects').children('option').filter(':selected').data('id');
            var currentMilestone = $(this).children('option').filter(':selected');

            var dateFrom = $('#inputSTDate').val();
            var dateTo = $('#inputENDate').val();


            $('.preload2').css({'display':'block', 'z-index':10000});



            $('#check_list').attr('disabled', 'true');
            if($('#check_list').prop('checked')){
                $('#check_list').prop('checked', false).change();
            }

            function create_Branches(branches) {
                var proj_id = $('option:selected').attr('data-id');
                var a_list = [];
                branches.forEach(function(item,i,branches){
                    var br_i = item.replace(/#/g,'%23');

                    $('#branches').append('<option data-i="http://gitlab.simplyhq.com/api/v4/projects/' + proj_id + '/repository/archive.zip?sha=' + br_i + '">' + item + '</option><br>');
                     var aaa = $('#branches').find("option").eq(i);
                     a_list.push(aaa.attr('data-i'));
                });
                $('#download').click(function(){

                    $('.preload').css('display','block');

                    var zip_a = $('#branches option:selected').data('i');
                    $.post('zip.php', {name: zip_a}, function(resp) {
                        // window.location.href = "/"+ resp.file;
                        // console.log(resp);
                        $('.preload').css('display','none');
                    }, "json")
                })

            }


            $.post('index.php?action=select_milestones',{milestone_id:  currentMilestone.val(), project_id: projectID,  dateFrom: dateFrom, dateTo: dateTo},  function (resp) {
                if(resp){

                    $('.preload2').css('display','none');

                    var selectedStyle = $('#select-container-list').children('option').filter(':selected');

                    if(selectedStyle.val() == 1) {
                        buildChart(resp.ideal, resp.actual);
                    } else if (selectedStyle.val() == 2){
                        buildSecondChart(resp.horiz_graph, resp.maxDate, resp.minDate);
                    }

                    show_list(resp.open_issues);

                    $('#branches').empty();
                    if(resp.open_issues && resp.open_issues.length){
                        $('#check_list').removeAttr('disabled');
                    }
                    if(resp.branch_name) {
                        create_Branches(resp.branch_name);
                    }
                }
            }, 'json');

        });




        $('#select-container-list').change(function(e){
            var selectedStyle = $('#select-container-list').children('option').filter(':selected');
            $('#sel-milestones').trigger('change');

            if(selectedStyle.val() == 2) {
                $('#container-burndown').css('overflow-y', 'scroll');
            } else if (selectedStyle.val() == 1) {
                $('#container-burndown').css('overflow', 'auto');
            }

        })











        $('div.date-filter button.submit').on('click', function(){
            var dateFrom = $('#inputSTDate').val();
            var dateTo = $('#inputENDate').val();
            var a_list = $('.list_class');
            a_list = [].slice.call(a_list);
            a_list.forEach(function(item,i,a_list) {
                item.style.display = 'block';
            });

            if(((dateFrom.length && dateTo.length) && (dateFrom <= dateTo)) || (!dateTo.length || !dateFrom.length)) {
                if(dateFrom.length){
                    dateFrom = moment(dateFrom, "YYYY-MM-DD").valueOf();
                }else{
                    dateFrom = null;
                    a_list.forEach(function(item,i,a_list) {
                        item.style.display = 'block';
                    });
                }


                if(dateTo.length){
                    dateTo = moment(dateTo, "YYYY-MM-DD").valueOf();
                }else{
                    dateTo = null;
                    a_list.forEach(function(item,i,a_list) {
                        item.style.display = 'block';
                    });
                }

                $(chart).highcharts().xAxis[0].setExtremes(dateFrom, dateTo);

                a_list.forEach(function(item,i,a_list) {
                    if(dateFrom != null && item.attributes.data_time.nodeValue < dateFrom) {
                        item.style.display = 'none';
                    }

                    if(dateTo != null && item.attributes.data_time.nodeValue > dateTo) {
                        item.style.display = 'none';
                    }
                });
            }
            else {
                $('.clrbtn').click();
            }



        });

        $('#check_list').on('change', function(){
            if($(this).prop('checked')) {
                $('#open_list ').slideDown(1000);
            } else {
                $('#open_list ').slideUp(1000);
            }
        });



        $('.clrbtn').on('click', function(){
            $('#inputSTDate').val(null);
            $('#inputENDate').val(null);
            $(chart).highcharts().xAxis[0].setExtremes(null, null);
            var a_list2 = $('.list_class');
            a_list2 = [].slice.call(a_list2);
            a_list2.forEach(function(item,i,a_list2){
                item.style.display = 'block';
            })

        })





    });


})(jQuery);