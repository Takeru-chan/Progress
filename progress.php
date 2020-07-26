<?php
  date_default_timezone_set('Asia/Tokyo');
  define('FILEPATH', pathinfo($_SERVER["SCRIPT_NAME"]));
  define('BASENAME', FILEPATH['basename']);
  define('JSONDATA', FILEPATH['filename'] . '.json');
  define('CONFIGDATA', FILEPATH['filename'] . '.conf');
  define('STARTDATE', date('U', mktime(0, 0, 0, date('m'), 1, date('Y'))));
  global $status;
  global $config;
  if(isset($_GET["mode"]) && $_GET["mode"] === "save" && isset($_POST["status"])){
    foreach($_POST["status"] as $value){
      if($value["title"] !== ""){
        $status[] = [
          "title" => htmlspecialchars($value["title"], ENT_QUOTES),
          "assignee" => htmlspecialchars($value["assignee"], ENT_QUOTES),
          "start" => !empty($value["start"]) ? strtotime($value["start"]) : null,
          "end" => !empty($value["end"]) ? strtotime($value["end"]) : null,
          "extend" => !empty($value["extend"]) ? strtotime($value["extend"]) : null,
          "study" => !empty($value["study"]) ? strtotime($value["study"]) : null,
          "check" => !empty($value["check"]) ? strtotime($value["check"]) : null,
          "revise" => !empty($value["revise"]) ? strtotime($value["revise"]) : null,
          "issued" => !empty($value["issued"]) ? strtotime($value["issued"]) : null,
          ];
      }
    }
    array_filter($status);
    file_put_contents(JSONDATA, json_encode($status, JSON_UNESCAPED_UNICODE));
    header('Location: ' . BASENAME);
  }elseif(isset($_GET["mode"]) && $_GET["mode"] === "save" && isset($_POST["config"])){
    $config = [
      "service_name" => htmlspecialchars($_POST["config"]["service_name"], ENT_QUOTES),
      "list_label" => htmlspecialchars($_POST["config"]["list_label"], ENT_QUOTES),
      "chart_label" => htmlspecialchars($_POST["config"]["chart_label"], ENT_QUOTES),
      "task_title" => htmlspecialchars($_POST["config"]["task_title"], ENT_QUOTES),
      "assignee" => htmlspecialchars($_POST["config"]["assignee"], ENT_QUOTES),
      "reception" => htmlspecialchars($_POST["config"]["reception"], ENT_QUOTES),
      "duedate" => htmlspecialchars($_POST["config"]["duedate"], ENT_QUOTES),
      "extension" => htmlspecialchars($_POST["config"]["extension"], ENT_QUOTES),
      "suspend" => htmlspecialchars($_POST["config"]["suspend"], ENT_QUOTES),
      "suspend_color" => $_POST["config"]["suspend_color"],
      "notstart_color" => $_POST["config"]["notstart_color"],
      "workinprogress" => htmlspecialchars($_POST["config"]["workinprogress"], ENT_QUOTES),
      "wip_color" => $_POST["config"]["wip_color"],
      "check" => htmlspecialchars($_POST["config"]["check"], ENT_QUOTES),
      "check_color" => $_POST["config"]["check_color"],
      "revise" => htmlspecialchars($_POST["config"]["revise"], ENT_QUOTES),
      "revise_color" => $_POST["config"]["revise_color"],
      "done" => htmlspecialchars($_POST["config"]["done"], ENT_QUOTES),
      "done_color" => $_POST["config"]["done_color"],
      "submit" => htmlspecialchars($_POST["config"]["submit"], ENT_QUOTES)
    ];
    file_put_contents(CONFIGDATA, json_encode($config, JSON_UNESCAPED_UNICODE));
    header('Location: ' . BASENAME);
  }elseif(isset($_GET["mode"]) && $_GET["mode"] === "clear"){
    if(file_exists(CONFIGDATA)){unlink(CONFIGDATA);}
    header('Location: ' . BASENAME);
  }
  define('ENDDATE', date('U', mktime(0, 0, 0, date('m') + 2, 0, date('Y'))));
  define('COLS', (int)date('t') + (int)date('d', ENDDATE));
  define('WIDTH', round((1080 / COLS) * COLS, 0));
  define('INTERVAL', WIDTH / COLS);
  define('STEPS', 20);
  define('HEADER', 40);
  define('HOLIDAYS', file_exists('holiday.txt') ? file_get_contents('holiday.txt') : null);
  $status = file_exists(JSONDATA) ? json_decode(file_get_contents(JSONDATA), true) : null;
  $config = file_exists(CONFIGDATA) ? json_decode(file_get_contents(CONFIGDATA), true) : null;
  define('STATUS_ROW', 70);
  define('BODY', STATUS_ROW * count($status) + 10);
  define('HEIGHT', HEADER + BODY);
  define('SERVICE_NAME', isset($config["service_name"]) ? $config["service_name"] : 'Progress');
  define('CHART_LABEL', isset($config["chart_label"]) ? $config["chart_label"] : "Change Chart view");
  define('LIST_LABEL', isset($config["list_label"]) ? $config["list_label"] : "Change List view");
  define('TASK_TITLE', isset($config["task_title"]) ? $config["task_title"] : "Task title");
  define('ASSIGNEE', isset($config["assignee"]) ? $config["assignee"] : "Assignee");
  define('RECEPTION', isset($config["reception"]) ? $config["reception"] : "Reception date");
  define('DUEDATE', isset($config["duedate"]) ? $config["duedate"] : "Due date");
  define('EXTENSION', isset($config["extension"]) ? $config["extension"] : "Extension date");
  define('SUSPEND', isset($config["suspend"]) ? $config["suspend"] : "Suspend");
  define('SUSPEND_COLOR', isset($config["suspend_color"]) ? $config["suspend_color"] : "black");
  define('NOTSTART_COLOR', isset($config["notstart_color"]) ? $config["notstart_color"] : "#cc99ff");
  define('WIP', isset($config["workinprogress"]) ? $config["workinprogress"] : "WIP");
  define('WIP_COLOR', isset($config["wip_color"]) ? $config["wip_color"] : "#00ccff");
  define('CHECK', isset($config["check"]) ? $config["check"] : "Check");
  define('CHECK_COLOR', isset($config["check_color"]) ? $config["check_color"] : "#00ff99");
  define('REVISE', isset($config["revise"]) ? $config["revise"] : "Revise");
  define('REVISE_COLOR', isset($config["revise_color"]) ? $config["revise_color"] : "#00ff33");
  define('DONE', isset($config["done"]) ? $config["done"] : "Done");
  define('DONE_COLOR', isset($config["done_color"]) ? $config["done_color"] : "#c0c0c0");
  define('SUBMIT', isset($config["submit"]) ? $config["submit"] : "Submit");
  function chart($array, $start_row){
    $start_col = $array["start"] < STARTDATE ? 0 : ($array["start"] - STARTDATE) / (60 * 60 * 24);
    $end_col = $array["end"] > ENDDATE ? COLS : ($array["end"] - STARTDATE) / (60 * 60 * 24);
    $extend_col = isset($array["extend"]) ? ($array["extend"] - STARTDATE) / (60 * 60 * 24) : -1;
    $color = NOTSTART_COLOR;
    if(!is_null($array["study"])){
      $study_col = ($array["study"] < STARTDATE) || ($array["study"] > ENDDATE) ? null : ($array["study"] - STARTDATE) / (60 * 60 * 24);
      $color = WIP_COLOR;
    }
    if(!is_null($array["check"])){
      $check_col = ($array["check"] < STARTDATE) || ($array["check"] > ENDDATE) ? null : ($array["check"] - STARTDATE) / (60 * 60 * 24);
      $color = CHECK_COLOR;
    }
    if(!is_null($array["revise"])){
      $revise_col = ($array["revise"] < STARTDATE) || ($array["revise"] > ENDDATE) ? null : ($array["revise"] - STARTDATE) / (60 * 60 * 24);
      $color = REVISE_COLOR;
    }
    if(!is_null($array["issued"])){
      $issued_col = ($array["issued"] < STARTDATE) || ($array["issued"] > ENDDATE) ? null : ($array["issued"] - STARTDATE) / (60 * 60 * 24);
      $color = DONE_COLOR;
    }
    if($array["assignee"] === "中断" || $array["assignee"] === SUSPEND){$color = SUSPEND_COLOR;}
    echo '<text x="0" y="' . $start_row . '" font-size="14" font-weight="bold" fill="#555">' . $array["title"];
    if($array["assignee"] !== ""){echo ' : ' . $array["assignee"];}
    echo '</text>';
    for($n = 0; $n < COLS; $n++){
      if(!($n < $start_col) && !($n > $end_col)){
        echo '<rect x="' . ($n * INTERVAL) . '" y="' . ($start_row + 10) . '" width="' . INTERVAL . '" height="10" fill="' . $color . '"></rect>';
      }elseif($n <= $extend_col && $n > $end_col){
        echo '<rect x="' . (($n + 0.5) * INTERVAL) . '" y="' . ($start_row + 10) . '" width="' . (INTERVAL / 2) . '" height="10" fill="' . $color . '" fill-opacity="0.3"></rect>';
      }
      if($study_col === $n){
        echo '<text x="' . ($n * INTERVAL) .'" y="' . ($start_row + 35) . '" font-size="12" fill="#555">&#x25b2; ' . WIP . '</text>';
      }
      if($check_col === $n){
        echo '<text x="' . ($n * INTERVAL) .'" y="' . ($start_row + 35) . '" font-size="12" fill="#555">&#x25b2; ' . CHECK . '</text>';
      }
      if($revise_col === $n){
        echo '<text x="' . ($n * INTERVAL) .'" y="' . ($start_row + 35) . '" font-size="12" fill="#555">&#x25b2; ' . REVISE . '</text>';
      }
      if($issued_col === $n){
        echo '<text x="' . ($n * INTERVAL) .'" y="' . ($start_row + 35) . '" font-size="12" fill="#555">&#x25b2; ' . DONE . '</text>';
      }
    }
  }
  define('QUERY', (isset($_GET["mode"]) && $_GET["mode"] === "list") ? "" : "?mode=list");
  define('LABEL', (isset($_GET["mode"]) && $_GET["mode"] === "list") ? CHART_LABEL :LIST_LABEL);
?>
<!doctype html>
<html lang='ja'>
<head>
  <meta charset='utf-8'>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://unpkg.com/purecss@2.0.3/build/pure-min.css" integrity="sha384-cg6SkqEOCV1NbJoCu11+bm0NvBRc8IYLRGXkmNrqUBfTjmMYwNKPWBTIKyw9mHNJ" crossorigin="anonymous">
  <title><?php echo SERVICE_NAME; ?></title>
  <link rel="icon" type="image/png" href="favicon.png">
  <link rel="apple-touch-icon" sizes="256x256" href="favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
  <style>
    *{font-family: 'M PLUS Rounded 1c', sans-serif;color:#444;}
    svg{border:solid 1px lightgrey;}
    body{width:<?php echo WIDTH; ?>px;margin:auto;}
    footer p{text-align:center;}
    .pure-menu-heading:hover{background:white;}
    .pure-control-group{padding:0.5em;}
    .flex-end{display:flex;justify-content:flex-end;padding:0;margin:0;}
  </style>
</head>
<body>
<div class="pure-menu pure-menu-horizontal">
  <a class="pure-menu-heading pure-menu-link " href="<?php echo BASENAME; ?>"><h1><?php echo SERVICE_NAME; ?></h1></a>
  <ul class="pure-menu-list">
    <li class="pure-menu-item"><a class="pure-button pure-menu-link" href="<?php echo BASENAME . QUERY; ?>"><?php echo LABEL; ?></a></li>
  </ul>
</div>
<a class="flex-end" href="<?php echo BASENAME . '?mode=setting'; ?>">Settings</a>
<?php if(isset($_GET["mode"]) && $_GET["mode"] === "list") : ?>
<form class="pure-form pure-form-stacked" method="post" action="<?php echo BASENAME . '?mode=save' ?>">
  <fieldset>
    <?php foreach($status as $key => $value) : ?>
    <hr>
    <div class="pure-g">
      <div class="pure-u-3-5 pure-control-group">
        <label for="title"><?php echo TASK_TITLE; ?></label>
        <input type="text" class="pure-input-1" id="title" name="status[<?php echo $key; ?>][title]" value="<?php echo $value["title"]; ?>"/>
      </div>
      <div class="pure-u-1-5 pure-control-group">
        <label for="assignee"><?php echo ASSIGNEE; ?></label>
        <input type="text" id="assignee" name="status[<?php echo $key; ?>][assignee]" value="<?php echo $value["assignee"]; ?>"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u pure-control-group">
        <label for="start"><?php echo RECEPTION; ?></label>
        <input type="date" id="start" name="status[<?php echo $key; ?>][start]" value="<?php if(!is_null($value["start"])){echo date('Y-m-d', $value["start"]);} ?>"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="end"><?php echo DUEDATE; ?></label>
        <input type="date" id="end" name="status[<?php echo $key; ?>][end]" value="<?php if(!is_null($value["end"])){echo date('Y-m-d', $value["end"]);} ?>"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="extend"><?php echo EXTENSION; ?></label>
        <input type="date" id="extend" name="status[<?php echo $key; ?>][extend]" value="<?php if(!is_null($value["extend"])){echo date('Y-m-d', $value["extend"]);} ?>"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u pure-control-group">
        <label for="study"><?php echo WIP; ?></label>
        <input type="date" id="study" name="status[<?php echo $key; ?>][study]" value="<?php if(!is_null($value["study"])){echo date('Y-m-d', $value["study"]);} ?>"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="check"><?php echo CHECK; ?></label>
        <input type="date" id="check" name="status[<?php echo $key; ?>][check]" value="<?php if(!is_null($value["check"])){echo date('Y-m-d', $value["check"]);} ?>"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="revise"><?php echo REVISE; ?></label>
        <input type="date" id="revise" name="status[<?php echo $key; ?>][revise]" value="<?php if(!is_null($value["revise"])){echo date('Y-m-d', $value["revise"]);} ?>"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="issued"><?php echo DONE; ?></label>
        <input type="date" id="issued" name="status[<?php echo $key; ?>][issued]" value="<?php if(!is_null($value["issued"])){echo date('Y-m-d', $value["issued"]);} ?>"/>
      </div>
    </div>
    <?php endforeach; ?>
    <hr>
    <div class="pure-g">
      <div class="pure-u-3-5 pure-control-group">
        <label for="title"><?php echo TASK_TITLE; ?></label>
        <input type="text" class="pure-input-1" id="title" name="status[<?php echo ($key + 1); ?>][title]"/>
      </div>
      <div class="pure-u-1-5 pure-control-group">
        <label for="assignee"><?php echo ASSIGNEE; ?></label>
        <input type="text" id="assignee" name="status[<?php echo ($key + 1); ?>][assignee]"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u pure-control-group">
        <label for="start"><?php echo RECEPTION; ?></label>
        <input type="date" id="start" name="status[<?php echo ($key + 1); ?>][start]"/>
      </div>
      <div class="pure-u pure-control-group">
        <label for="end"><?php echo DUEDATE; ?></label>
        <input type="date" id="end" name="status[<?php echo ($key + 1); ?>][end]"/>
      </div>
    </div>
    <div class="pure-controls">
      <button type="submit" class="pure-button pure-button-primary"><?php echo SUBMIT; ?></button>
    </div>
  </fieldset>
</form>
<?php elseif(isset($_GET["mode"]) && $_GET["mode"] === "setting") : ?>
<h2>Settings</h2>
<form class="pure-form pure-form-stacked" method="post" action="<?php echo BASENAME . '?mode=save' ?>">
  <fieldset>
    <hr>
    <h3>List view setting</h3>
    <div class="pure-g">
      <div class="pure-u-7-24 pure-control-group">
        <label for="service_name">Service name</label>
        <input type="text" class="pure-input-1" id="service_name" name="config[service_name]" value="<?php echo SERVICE_NAME; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="list_label">Change List view</label>
        <input type="text" class="pure-input-1" id="list_label" name="config[list_label]" value="<?php echo LIST_LABEL; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="chart_label">Change Chart view</label>
        <input type="text" class="pure-input-1" id="chart_label" name="config[chart_label]" value="<?php echo CHART_LABEL; ?>"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u-7-24 pure-control-group">
        <label for="task_title">Task title</label>
        <input type="text" class="pure-input-1" id="task_title" name="config[task_title]" value="<?php echo TASK_TITLE; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="assignee">Assignee</label>
        <input type="text" class="pure-input-1" id="assignee" name="config[assignee]" value="<?php echo ASSIGNEE; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="submit">Submit</label>
        <input type="text" class="pure-input-1" id="submit" name="config[submit]" value="<?php echo SUBMIT; ?>"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u-7-24 pure-control-group">
        <label for="reception">Reception date</label>
        <input type="text" class="pure-input-1" id="reception" name="config[reception]" value="<?php echo RECEPTION; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="duedate">Due date</label>
        <input type="text" class="pure-input-1" id="duedate" name="config[duedate]" value="<?php echo DUEDATE; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="extension">Extension date</label>
        <input type="text" class="pure-input-1" id="extension" name="config[extension]" value="<?php echo EXTENSION; ?>"/>
      </div>
    </div>
    <hr>
    <h3>Chart view setting</h3>
    <div class="pure-g">
      <div class="pure-u-7-24 pure-control-group">
        <label for="suspend">Assignee for suspend</label>
        <input type="text" class="pure-input-1" id="suspend" name="config[suspend]" value="<?php echo SUSPEND; ?>"/>
        <label for="suspend_color">Color</label>
        <input type="color" class="pure-input-1" id="suspend_color" name="config[suspend_color]" value="<?php echo SUSPEND_COLOR; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="notstart_color">Not started color</label>
        <input type="color" class="pure-input-1" id="notstart_color" name="config[notstart_color]" value="<?php echo NOTSTART_COLOR; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="workinprogress">WIP (Work In Progress)</label>
        <input type="text" class="pure-input-1" id="workinprogress" name="config[workinprogress]" value="<?php echo WIP; ?>"/>
        <label for="wip_color">Color</label>
        <input type="color" class="pure-input-1" id="wip_color" name="config[wip_color]" value="<?php echo WIP_COLOR; ?>"/>
      </div>
    </div>
    <div class="pure-g">
      <div class="pure-u-7-24 pure-control-group">
        <label for="check">Check</label>
        <input type="text" class="pure-input-1" id="check" name="config[check]" value="<?php echo CHECK; ?>"/>
        <label for="check_color">Color</label>
        <input type="color" class="pure-input-1" id="check_color" name="config[check_color]" value="<?php echo CHECK_COLOR; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="revise">Revise</label>
        <input type="text" class="pure-input-1" id="revise" name="config[revise]" value="<?php echo REVISE; ?>"/>
        <label for="revise_color">Color</label>
        <input type="color" class="pure-input-1" id="revise_color" name="config[revise_color]" value="<?php echo REVISE_COLOR; ?>"/>
      </div>
      <div class="pure-u-7-24 pure-control-group">
        <label for="done">Done</label>
        <input type="text" class="pure-input-1" id="done" name="config[done]" value="<?php echo DONE; ?>"/>
        <label for="done_color">Color</label>
        <input type="color" class="pure-input-1" id="done_color" name="config[done_color]" value="<?php echo DONE_COLOR; ?>"/>
      </div>
    </div>
    <div class="pure-controls">
      <button type="submit" class="pure-button pure-button-primary">Submit</button>
      <a class="pure-button" href="<?php echo BASENAME . '?mode=clear'; ?>">Clear</a>
    </div>
  </fieldset>
</form>
<?php else : ?>
<svg width="<?php echo WIDTH; ?>px" height="<?php echo HEIGHT; ?>px" viewbox="0 0 <?php echo WIDTH . ' ' . HEIGHT; ?>">
<?php
  $startx = 0;
  for($n = 0; $n < COLS; $n++){
    $unixtime = date('U', mktime(0, 0, 0, date('m'), $n + 1, date('Y')));
    $date = (int)date('j', $unixtime);
    $week = (int)date('w', $unixtime);
    if(date('j') == $n + 1){
      $bg_color = '#ffffaa';
    }elseif($week === 0 || $week === 6 || strpos(HOLIDAYS, date('Ymd', $unixtime)) !== false){
      $bg_color = '#ffd5ec';
    }elseif($week === 2 || $week === 4){
      $bg_color = '#fcfcfc';
    }else{
      $bg_color = 'white';
    }
    echo '<rect x="' . $startx . '" y="' . HEADER . '" width="' . INTERVAL . '" height="' . BODY . '" fill="' . $bg_color . '"></rect>';
    if($date === 1){
      echo '<text x="' . ($startx + 2) .'" y="' . (STEPS - 2) . '" font-size="' . (STEPS - 4) . '" fill="#555">' . date('F', mktime(0, 0, 0, date('m'), $n + 1, date('Y'))) . '</text>';
    }
    if($week === 1){
      echo '<text x="' . $startx .'" y="' . (STEPS * 2 - 4) . '" font-size="' . (STEPS - 8) . '" fill="#555">' . date('j', mktime(0, 0, 0, date('m'), $n + 1, date('Y'))) . '</text>';
    }
    $startx += INTERVAL;
  }

  for($n = 0; $n < count($status); $n++){
    chart($status[$n], HEADER + 20 + $n * STATUS_ROW);
  }
?>
</svg>
<?php endif; ?>
<hr>
<footer class="pure-g">
  <p class="pure-u-1-3">Progress v1.0 built with <a href="https://purecss.io/">Pure v2.0.3</a>.</p>
  <p class="pure-u-1-3">&copy; 2020, Takeru-chan.</p>
  <p class="pure-u-1-3">Released under <a href="http://opensource.org/licenses/mit-license.php">the MIT license</a>.</p>
</footer>
</body>
</html>
