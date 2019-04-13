<?php
// a simple string to timestamp function for any format
function timestamp_from_date($date, $format) {
  $t = DateTime::createFromFormat('!' . $format, $date);
  return $t->getTimestamp();
}

 ?>
