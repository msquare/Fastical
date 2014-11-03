<?php
header('Content-Type:text/plain; charset=utf-8');

$f = new Fastical('OFfjVrPv.ics');
print_r($f->getEvents(time(), time() + 7 * 24 * 60 * 60));

class Fastical {

  var $ics_file = null;

  function __construct($ics_file) {
    if (! file_exists($ics_file))
      throw new Exception("File not found.");
    
    $this->ics_file = $ics_file;
  }

  function getEvents($start, $end) {
    if ($start > $end)
      throw new Exception("Start has to be before end.");
    
    $fd = @fopen($this->ics_file, 'r');
    if ($fd == null)
      throw new Exception("Unable to open ics file.");
    
    $events = array();
    while (($buffer = fgets($fd)) !== false) {
      if (rtrim($buffer) == 'BEGIN:VCALENDAR') {
        $events = $this->parseVCalendar($fd, $start, $end);
        break;
      }
    }
    
    if ($fd != null)
      fclose($fd);
    
    return $events;
  }

  private function parseLine($line) {
    if (! strstr($line, ':'))
      return null;
    list($key, $value) = explode(':', $line);
    $params = array();
    if (strstr($key, ';')) {
      $tmp = explode(';', $key);
      $key = $tmp[0];
      for ($i = 1; $i < count($tmp); $i ++) {
        list($param_key, $param_value) = explode('=', $tmp[$i]);
        $params[$param_key] = $param_value;
      }
    }
    
    return array(
        'key' => $key,
        'value' => array(
            'value' => $value,
            'params' => $params 
        ) 
    );
  }

  private function parseVEvent($fd, $start, $end) {
    $events = array();
    $event = array();
    $line = '';
    while (($buffer = fgets($fd)) !== false) {
      if (rtrim($buffer) == 'BEGIN:VALARM')
        $this->parseVAlarm($fd, $start, $end);
      
      if (rtrim($buffer) == 'END:VEVENT')
        break;
      
      if (substr($buffer, 0, 1) == ' ')
        $line .= substr(rtrim($buffer), 1);
      else {
        if ($line != '') {
          $line_data = $this->parseLine($line);
          if ($line_data != null)
            $event[$line_data['key']] = str_replace('\\\\,', ',', $line_data['value']);
        }
        $line = rtrim($buffer);
      }
    }
    
    foreach ($this->validateVEvent($event, $start, $end) as $event)
      array_push($events, $event);
    
    return $events;
  }

  private function validateVEvent($event, $start, $end) {
    $events = array();
    
    $dtstart = null;
    if (isset($event['DTSTART']))
      $dtstart = strtotime($event['DTSTART']['value']);
    
    if ($dtstart > $end)
      return $events;
    
    $dtend = null;
    if (isset($event['DTEND']))
      $dtend = strtotime($event['DTEND']['value']);
    
    if ($dtend < $start)
      return $events;
    
    $summary = null;
    if (isset($event['SUMMARY']))
      $summary = $event['SUMMARY']['value'];
    
    $location = null;
    if (isset($event['LOCATION']))
      $location = $event['LOCATION']['value'];
    
    $events[] = array(
        'start' => $dtstart,
        'end' => $dtend,
        'summary' => $summary,
        'location' => $location 
    );
    
    return $events;
  }

  private function parseVAlarm($fd, $start, $end) {
    while (($buffer = fgets($fd)) !== false)
      if (rtrim($buffer) == 'END:VALARM')
        return;
  }

  private function parseVCalendar($fd, $start, $end) {
    $events = array();
    while (($buffer = fgets($fd)) !== false) {
      switch (rtrim($buffer)) {
        case 'BEGIN:VEVENT':
          foreach ($this->parseVEvent($fd, $start, $end) as $event)
            array_push($events, $event);
          break;
        
        case 'END:VCALENDAR':
          return $events;
      }
    }
  }
}

?>