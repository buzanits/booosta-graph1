<?php
namespace booosta\graph1;

use \booosta\Framework as b;
b::init_module('graph1');

class Graph1 extends \booosta\ui\UI
{
  use moduletrait_graph1;

  protected $id;
  protected $title;
  protected $data;
  protected $height, $width;
  protected $options, $colors, $labels, $tooltip = false;
  protected $mode = 'number';
  protected $minval, $maxval;

  protected $default_options =
  [
    'grid' => ['hoverable' => 'true', 'borderColor' => '#f3f3f3'],
    'series' => ['shadowsize' => '0']
  ];


  public function __construct($name, $data = null, $title = null, $height = 300, $width = 400)
  {
    parent::__construct();

    $this->id = "graph1_$name";
    if ($data === null) $data = [];
    $this->data = $data;
    $this->title = $title;
    $this->height = $height;
    $this->width = $width;
    $this->options = [];
  }

  public function set_title($title) { $this->title = $title; }
  public function set_data($data) { $this->data = $data; }
  public function set_height($val) { $this->height = $val; }
  public function set_width($val) { $this->width = $val; }
  public function set_colors($val) { $this->colors = $val; }
  public function set_labels($val) { $this->labels = $val; }
  public function set_mode($val) { $this->mode = $val; }
  public function set_tooltip($val = true) { $this->tooltip = $val; }
  public function set_minval($val = true) { $this->minval = $val; }
  public function set_maxval($val = true) { $this->maxval = $val; }

  public function show_legend()
  {
    $this->set_option('legend', 'show', 'true');
  }

  public function show_points()
  {
    $this->set_option('series', 'points', ['show' => 'true']);
    $this->set_option('series', 'lines', ['show' => 'true']);
  }

  public function set_option($name, $value, $value1 = null)
  {
    if ($value1 === null) $this->options[$name] = $value;
    else $this->options[$name][$value] = $value1;
  }

  public function add_data($data)
  {
    if (is_array($data)) $this->data = array_merge($this->data, $data);
    else $this->data[] = $data;
  }

  public function get_html_includes($libpath = 'lib/modules/graph1') {}

  public function get_htmlonly()
  {
    #return "<div id='$this->id' style='height: 100%'></div>";
    return "<div id='$this->id' style='height: {$this->height}px;'></div>";
    #return "<div class='graph1_container' style='width: {$this->width}px; height: {$this->height}px;'> <div id='$this->id' class='graph1_div'></div> </div>";
  }

  public function get_js()
  {
    if (!is_array($this->data)) return '';
    $linearr = [];

    // set options depending on $this->mode
    switch ($this->mode):
      case 'date':
        $this->set_option('xaxis', 'mode', 'time');
        $this->set_option('xaxis', 'timeBase', 'seconds');
        $this->set_option('xaxis', 'timeformat', '%d.%m.');
        $this->set_option('xaxis', 'timezone', 'browser');
        $this->convert_x_timestamp();
        break;
      case 'datetime':
        $this->set_option('xaxis', 'mode', 'time');
        $this->set_option('xaxis', 'timeBase', 'seconds');
        $this->set_option('xaxis', 'timeformat', '%d.%m. %H:%M');
        $this->set_option('xaxis', 'timezone', 'browser');
        $this->convert_x_timestamp();
        break;
      case 'time':
        $this->set_option('xaxis', 'mode', 'time');
        $this->set_option('xaxis', 'timeBase', 'seconds');
        $this->set_option('xaxis', 'timeformat', '%H:%M');
        $this->set_option('xaxis', 'timezone', 'browser');
        $this->convert_x_timestamp();
        break;
    endswitch;

    // if min or max is set, the other must be calculated
    $calc_minval = false;
    $calc_maxval = false;

    if(is_numeric($this->minval) && !is_numeric($this->maxval)) $calc_maxval = true;
    elseif(!is_numeric($this->minval) && is_numeric($this->maxval)) $calc_minval = true;

    foreach ($this->data as $index => $line) :
      if (!is_array($line)) continue;
      $pointarr = [];

      foreach ($line as $point) :
        if (!is_array($point)) continue;

        $pointarr[] = "[{$point[0]},{$point[1]}]";
        if($calc_minval && ($this->minval === null || $this->minval > $point[1])) $this->minval = $point[1];
        if($calc_maxval && ($this->maxval === null || $this->maxval < $point[1])) $this->maxval = $point[1];
      endforeach;

      if ($this->labels[$index]) $label = $this->labels[$index];
      elseif (is_string($index)) $label = $index;
      else $label = '';

      if ($label) $linearr[] = "{ label: '$label', data: [" . implode(',', $pointarr) . '], }';
      else $linearr[] = '[' . implode(',', $pointarr) . ']';
    endforeach;

    if(!is_numeric($this->minval) && !is_numeric($this->maxval)):
      $this->set_option('yaxis', 'autoScale', 'loose');
    else:
      $this->set_option('yaxis', 'autoScale', 'none');
      $this->set_option('yaxis', 'min', $this->minval);
      $this->set_option('yaxis', 'max', $this->maxval);
    endif;

    $lines = implode(',', $linearr);

    // get options
    $options = $this->get_options();

    // show tooltip
    if ($this->tooltip !== false) :
      if ($this->tooltip === true) $tooltext = "{label} von {x} = {y}";
      else $tooltext = $this->tooltip;

      $tooltext = "'$tooltext'";
      $tooltext = str_replace(['{label}', '{x}', '{y}'], ["' + item.series.label + '", "' + x + '", "' + y + '"], $tooltext);

      $libpath = 'lib/modules/graph1';
      $tpl = file_get_contents("$libpath/tooltip.tpl");

      $calculations = '';
      if (in_array($this->mode, ['date', 'time', 'datetime'])) $calculations = "x = new Date(x*1000).toLocaleString('de-DE', {month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'}) ";
      $toolcode = str_replace(['{id}', '{tooltext}', '{calculations}'], [$this->id, $tooltext, $calculations], $tpl);
    else :
      $toolcode = '';
    endif;

    if (is_object($this->topobj) && is_a($this->topobj, '\booosta\webapp\webapp')) :
      $this->topobj->add_jquery_ready("\$.plot('#$this->id', [ $lines ],\n $options); $toolcode");
    else :
      return "\$(document).ready(function(){ \$.plot('#$this->id', [ $lines ],\n $options ); $toolcode});";
    endif;
  }

  protected function get_options()
  {
    $options = array_replace_recursive($this->default_options, $this->options);

    #\booosta\debug($options);
    #\booosta\debug($this->get_suboptions($options));

    $result = $this->get_suboptions($options);

    return $result;
  }

  protected function get_suboptions($options)
  {
    if(is_array($options)):
      $result = '';
      foreach ($options as $name => $opt) $result .= " $name: " . $this->get_suboptions($opt) . ", ";
      if (is_array($this->colors)) $result .= 'colors: ["' . implode('", "', $this->colors) . '"], ';

      return " { $result } ";
    endif;

    return "'$options'";
    #return is_numeric($options) ? $options : "'$options'";
  }

  protected function convert_x_timestamp()
  {
    foreach ($this->data as $i => $d)
      foreach ($d as $j => $data)
        $this->data[$i][$j][0] = strtotime($data[0]);
  }
}
