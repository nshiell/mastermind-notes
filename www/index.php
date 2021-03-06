<?php require_once (__DIR__ . '/../go.php'); go(!empty ($hasRun)) ?><!doctype html>
<html lang="en-us">
<head>
  <meta charset="utf-8" />
  <title>Mastermind Notes</title>
  <script type="text/javascript" src="/js-plain-calendar/js/nshiell-js-plain-calendar.js?cb=0"></script>
  <script type="text/javascript" src="/js/mastermind-notes.js?cb=0"></script>

  <link rel="stylesheet" href="/css/page.css" />
  <style>
  </style>
</head>
<body class="<?php if ($_SESSION['authorized']) echo 'logged-in' ?>">
<p><a href="/logout.php">Logout</a></p>
<div class="today pane">
  <h1>Today's Things:</h1>
  <table></table>
</div>

<div class="calendar pane"></div>

<form class="pane editor" onsubmit="saveCurrent(); return false">
  <label>
    <span class="label">Body:</span>
    <textarea name="body"></textarea>
  </label>

  <div class="date-container">
    <div class="date-picker start-picker"></div>
    <br />
    <input name="startHour" class="time" type="number" placeholder="00" pattern="[0-9][0-9]" />
    <input name="startMinute" class="time" type="number" placeholder="00" pattern="[0-9][0-9]" />
    <label>
      <span class="label">Start:</span>
      <input type="text" class="date-time-raw" name="dateTimeStart" pattern=
             "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]"
             placeholder="2019-10-10T22:35:17"
             value="2019-10-10T22:35:17" />
    </label>
  </div>

  <label>
    <span class="label">End:</span>
    <input type="text" name="dateTimeEnd" pattern=
           "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]:[0-9][0-9]"
           placeholder="2019-10-10T22:35:17"
           value="2019-10-10T22:35:17" />
  </label>

  <div>
    <input type="submit" value="Save" />
  </div>
</form>

<div class="day pane">
</div>

<div class="pane notes">
</div>

</body>
</html>