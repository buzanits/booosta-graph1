<?php
namespace booosta\graph1;

\booosta\Framework::add_module_trait('webapp', 'graph1\webapp');

trait webapp
{
  protected function preparse_graph1()
  {
    #$libpath = 'vendor/npm-asset/flot';
    $libpath = 'vendor/almasaeed2010/adminlte/plugins/flot';

    if($this->moduleinfo['graph1']):
      $this->add_includes("<script type='text/javascript' src='{$this->base_dir}{$libpath}/jquery.flot.js'></script>
            <script type='text/javascript' src='{$this->base_dir}{$libpath}/plugins/jquery.flot.time.js'></script>
            <link rel='stylesheet' type='text/css' href='{$this->base_dir}vendor/booosta/graph1/src/jquery.flot.css' media='screen' />");
    endif;
  }
}
