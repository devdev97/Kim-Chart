<?php

require 'vendor/autoload.php';

class BurndownChart
{

    private $header = array('PRIVATE-TOKEN: S2rNfKstFUgzWwC1U-Uf');

    public function __construct()
    {
        $this->checkRequest();
    }

    private function connection_to_gitlab($url)
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->header);

        curl_setopt($connection, CURLOPT_URL, $url);

        $result = curl_exec($connection);

        curl_close($connection);

        return json_decode($result, true);
    }

    public function get_all_project()
    {
        $all_projects = $this->connection_to_gitlab('http://gitlab.simplyhq.com/api/v4/projects?per_page=100');


        $name_projects = array();
        foreach ($all_projects as $item) {
            $name_projects[] = array(
                'id' => $item['id'],
                'name' => $item['name'],
                'has_issues' => $item['open_issues_count']
            );
        }

        usort($name_projects, function ($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        return $name_projects;
    }

    public function get_all_milestones_by_project($id)
    {
        $all_milestone = $this->connection_to_gitlab('http://gitlab.simplyhq.com/api/v4/projects/' . $id . '/milestones?per_page=100');
        $milestone = array();
        foreach ($all_milestone as $item) {
            $milestone[] = array(
                'created_at' => $this->toUnix($item['created_at']),
                'name' => $item['title'],
                'id' => $item['id'],
                'end_date' => $item['due_date'],
                'start_date' => $item['start_date']
            );
        }
        usort($milestone, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        return $milestone;

    }

    private function checkRequest()
    {
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'select_project') {

            if (!empty($_REQUEST['id'])) {
                $response = $this->get_all_milestones_by_project($_REQUEST['id']);
                die(json_encode($response));
            }

        } elseif (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'select_milestones') {
            if (!empty($_REQUEST['project_id']) && isset($_REQUEST['milestone_id'])) {

                $response = $this->get_graph_data($_REQUEST['project_id'], $_REQUEST['milestone_id'], '', '', $_REQUEST['selectedStyleValue']);

                die(json_encode($response));
            }
        }
    }

    private function getRelativePath($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $from = explode('/', $from);
        $to = explode('/', $to);
        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }


    private function getIssues($project_id, $milestoneFlag, $dateFrom, $dateTo, $milestone_id = false)
    {

        $link = "http://gitlab.simplyhq.com/api/v4/projects/{$project_id}/";
        if ($milestone_id) {
            $link .= "/milestones/{$milestone_id}/issues/";
        } else {
            $link .= "issues/";
        }
        $link .= '?per_page=2000';

        $issues = $this->connection_to_gitlab($link);
        if (!empty($issues)) {
            $moreIssues = true;
            $page = 2;

            while ($moreIssues) {
                $newPage = $this->connection_to_gitlab($link . '&page=' . $page);
                if (!empty($newPage)) {
                    $issues = array_merge($issues, $newPage);
                    $page++;
                } else {
                    $moreIssues = false;
                    break;
                }
            }
        }

        if (!$milestone_id && $milestoneFlag !== 'all_issues') {
            $issues = array_filter($issues, function ($issue) {
                return isset($issue['milestone']) && !empty($issue['milestone']);
            });
        }

        return $issues;
    }

    private function getChartDates($issues, $hasMilestone = false)
    {
        $dates = array();
        if ($hasMilestone) {
            $dates['start'] = !is_null($issues[0]['milestone']['start_date']) ? $this->toUnix($issues[0]['milestone']['start_date']) : $this->toUnix($issues[0]['milestone']['created_at']);
            $dates['end'] = !is_null($issues[0]['milestone']['due_date']) ? $this->toUnix($issues[0]['milestone']['due_date']) : $this->toUnix($issues[0]['milestone']['updated_at']);
        } else {
            $self = $this;
            usort($issues, function ($a, $b) use ($self) {
                if (($a['milestone']['start_date'] == false || is_null($a['milestone']['start_date'])) && ($b['milestone']['start_date'] == false || is_null($b['milestone']['start_date']))) {
                    $t1 = $self->toUnix($a['created_at']);
                    $t2 = $self->toUnix($b['created_at']);
                } else {
                    if ($a['milestone']['start_date'] == false || is_null($a['milestone']['start_date'])) {
                        $t1 = $self->toUnix($a['created_at']);
                        $t2 = $b['milestone']['start_date'] ? $self->toUnix($b['milestone']['start_date']) : $self->toUnix($b['milestone']['created_at']);
                    } else {
                        if ($b['milestone']['start_date'] == false || is_null($b['milestone']['start_date'])) {
                            $t1 = $a['milestone']['start_date'] ? $self->toUnix($a['milestone']['start_date']) : $self->toUnix($a['milestone']['created_at']);
                            $t2 = $self->toUnix($b['created_at']);
                        } else {
                            $t1 = !is_null($a['milestone']['start_date']) ? $self->toUnix($a['milestone']['start_date']) : $self->toUnix($a['milestone']['created_at']);
                            $t2 = !is_null($b['milestone']['start_date']) ? $self->toUnix($b['milestone']['start_date']) : $self->toUnix($b['milestone']['created_at']);
                        }
                    }
                }
                return $t1 - $t2;

            });
            $dates['start'] = !is_null($issues[0]['milestone']['start_date']) ? $this->toUnix($issues[0]['milestone']['start_date']) : $this->toUnix($issues[0]['milestone']['created_at']);
            if ($dates['start'] == false || $dates['start'] == null) {
                $dates['start'] = $this->toUnix($issues[0]['created_at']);
            }

            usort($issues, function ($a, $b) use ($self) {
                if (($a['milestone']['due_date'] == false || is_null($a['milestone']['due_date'])) && ($b['milestone']['due_date'] == false || is_null($b['milestone']['due_date']))) {
                    $t1 = $self->toUnix($a['updated_at']);
                    $t2 = $self->toUnix($b['updated_at']);
                } else {
                    if ($a['milestone']['due_date'] == false || is_null($a['milestone']['due_date'])) {
                        $t1 = $self->toUnix($a['updated_at']);
                        $t2 = $b['milestone']['due_date'] ? $self->toUnix($b['milestone']['due_date']) : $self->toUnix($b['milestone']['updated_at']);
                    } else {
                        if ($b['milestone']['due_date'] == false || is_null($b['milestone']['due_date'])) {
                            $t1 = $a['milestone']['due_date'] ? $self->toUnix($a['milestone']['due_date']) : $self->toUnix($a['milestone']['updated_at']);
                            $t2 = $self->toUnix($b['updated_at']);
                        } else {
                            $t1 = !is_null($a['milestone']['due_date']) ? $self->toUnix($a['milestone']['due_date']) : $self->toUnix($a['milestone']['updated_at']);
                            $t2 = !is_null($b['milestone']['due_date']) ? $self->toUnix($b['milestone']['due_date']) : $self->toUnix($b['milestone']['updated_at']);
                        }
                    }
                }
                return $t1 - $t2;
            });

            $lastMilestoneIssue = end($issues);

            $dates['end'] = $lastMilestoneIssue['milestone']['due_date'] ? $this->toUnix($lastMilestoneIssue['milestone']['due_date']) : $this->toUnix($lastMilestoneIssue['milestone']['updated_at']);

            if ($dates['end'] == false || is_null($dates['end'])) {
                $dates['end'] = $this->toUnix($lastMilestoneIssue['updated_at']);
            }
        }
        return $dates;
    }

    public function toUnix($date)
    {
        $seconds = false;

        if (!empty($date) && strpos($date, 'Z') === false) {
            $date .= ' 00:00:00';
            $d = date_create_from_format('Y-m-d H:i:s', $date, new DateTimeZone('UTC'));
            $seconds = date_timestamp_get($d);
        } else {
            $seconds = strtotime($date);
        }

        return $seconds;
    }

    public function get_horiz_data($milestoneList)
    {
        $horiz_graph = array();
        foreach ($milestoneList as $item) {
            $itemId = $item['id'];
            $horiz_graph[] = array(
                'taskName' => $item['name'],
                'id' => "$itemId",
                'start' => $this->toUnix($item['start_date']) * 1000,
                'end' => $this->toUnix($item['end_date']) * 1000,
                'className' => 'parent_class'
            );
        }
        return $horiz_graph;
    }

    public function get_graph_data($project_id, $milestoneFlag, $dateFrom, $dateTo, $styleValue)
    {
        $milestone_id = false;
        if ($milestoneFlag !== 'all_issues' && $milestoneFlag !== 'all_milestones') {
            $milestone_id = $milestoneFlag;
        }

        $all_issues = $this->getIssues($project_id, $milestoneFlag, $dateFrom, $dateTo, $milestone_id);

        $open_issues = array();

        if (empty($all_issues)) {
            return array(
                'ideal' => array(),
                'actual' => array(),
                'all_issues' => array()
            );
        }

        $dates = $this->getChartDates($all_issues, $milestone_id);
        $all_issues_count = count($all_issues);
        $ideal = array(
            array(
                'date' => $this->toUnix(date('Y-m-d', $dates['start'])),
                'items' => $all_issues_count
            )
        );

        $velocity = $all_issues_count / ($dates['end'] - $dates['start']);

        $self = $this;
        usort($all_issues, function ($a, $b) use ($self) {
            $t1 = $self->toUnix($a['updated_at']);
            $t2 = $self->toUnix($b['updated_at']);
            return $t1 - $t2;
        });

        if($styleValue == '2') {
            $milestoneList = $this->get_all_milestones_by_project($project_id);
            $horiz_graph = $this->get_horiz_data($milestoneList);
        }

        $allIssuesGraph = array();
        $counter = 0;
        $currentIssues = 0;
        $iss = array();
        foreach ($all_issues as $item) {
            $currentIssues++;
            $checkKey = date('Y-m-d', $this->toUnix($item['created_at']));
            $counter++;

            //add each processed issue to array, or increase counter if issues for this date were already counted
            if(isset($iss[$checkKey]['open'])){
                $iss[$checkKey]['open']++;
            }else{
                if(isset($iss[$checkKey])){
                    $iss[$checkKey]['open'] = 1;
                }else{
                    $iss[$checkKey] = array(
                        'open' => 1,
                        'dateRaw' => $checkKey
                    );
                }
            }

            if ($item['state'] === 'closed') {
                $checkKey = !empty($item['closed_at']) ?
                    date('Y-m-d', $this->toUnix($item['closed_at'])) :
                    date('Y-m-d', $this->toUnix($item['updated_at']));

                //Set or increased closed issues counter for the item's date
                if(isset($iss[$checkKey]['closed'])){
                    $iss[$checkKey]['closed']++;
                }else{
                    if(isset($iss[$checkKey])){
                        $iss[$checkKey]['closed'] = 1;
                    }else{
                        $iss[$checkKey] = array(
                            'closed' => 1,
                            'dateRaw' => $checkKey
                        );
                    }
                }
            	$counter--;
            } else {
                $open_issues[] = $item;
            }

            $allIssuesGraph[$checkKey] = array(
              'date' => $this->toUnix($item['updated_at']),
              'items' =>   $currentIssues
            );

            $milestoneId = $item['milestone']['id'];
            if (isset($item['due_date']) && $item['due_date'] != null) {
                $item_end_date = $this->toUnix($item['due_date']) * 1000;
            } else {
                $item_end_date = $this->toUnix($item['milestone']['due_date']) * 1000;
            }

            if(isset($horiz_graph)) {
                $horiz_graph[] = array(
                    'taskName' => $item['title'],
                    'id' => $item['id'],
                    'parent' => "$milestoneId",
                    'start' => $this->toUnix($item['milestone']['start_date']) * 1000,
                    'end' => $item_end_date,
                    'className' => 'child_class'
                );
            }
        }

        $branchList = $this->connection_to_gitlab('http://gitlab.simplyhq.com/api/v4/projects/' . $_REQUEST["project_id"] . '/repository/branches?per_page=2000');
        $branch_name = array();
        foreach ($branchList as $k => $v) {
            $branch_name[$k] = $branchList[$k]['name'];
        }

        ksort($iss);
        $iss = array_values($iss);

        $clean_iss = array();
        //Calculate actual points for chart
        foreach ($iss as $k => $item) {
            $clean = array(
                'date' => $this->toUnix($item['dateRaw']),
                'readableDate' => $item['dateRaw']
            );

            $clean['open'] = 0;
            if(isset($item['open'])){
                $clean['open'] = $item['open'];
            }

            $clean['closed'] = 0;
            if(isset($item['closed'])){
                $clean['closed'] = $item['closed'];
            }

            if(isset($clean_iss[$k - 1])){
                $prev = $clean_iss[$k - 1];
                $clean['prev'] = $prev;
                if(isset($prev['open'])){
                    $clean['open'] += $prev['open'];
                }

                if(isset($prev['closed'])){
                    $clean['closed'] += $prev['closed'];
                }
            }

            $clean['actual'] = $clean['open'] - $clean['closed'];
            $clean_iss[$k] = $clean;

            //Setup adjacent points on ideal line based on $velocity (task burn speed)
            $it = ($clean['date'] - $dates['start']) * $velocity;
            if (($k === 0 && $it <= $all_issues_count) || $it < 0) {
                continue;
            } else {
                if ($it > $all_issues_count) {
                    break;
                }
            };

            $ideal[] = array(
                'date' => $clean['date'],
                'items' => floor($all_issues_count - $it)
            );
        }

        $ideal = array_values($ideal);

        $horiz_graph = isset($horiz_graph) ? array_values($horiz_graph) : array();
        $allIssuesGraph = array_values($allIssuesGraph);
        return compact("ideal",  "all_issues", 'open_issues', 'branch_name', 'horiz_graph',
            'maxDate', 'minDate', 'iss', 'clean_iss');
    }
}

$chart = new BurndownChart();