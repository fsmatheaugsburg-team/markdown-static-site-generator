<?php

function timestamp_from_date($date, $format) {
  $t = DateTime::createFromFormat($format, $date);
  return $t->getTimestamp();
}

 ?>
