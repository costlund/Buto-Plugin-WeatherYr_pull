<?php
class PluginWeatherYr_pull{
  public $settings = null;
  public $contents = null;
  public $url = null;
  private $builder = null;
  private $mysql = null;
  function __construct() {
    wfPlugin::includeonce('wf/array');
    $this->settings = new PluginWfArray(wfPlugin::getPluginModulesOne('weather/yr_pull')->get('settings'));
    /**
     * 
     */
    wfPlugin::includeonce('mysql/builder');
    $this->builder = new PluginMysqlBuilder();
    $this->builder->set_schema_file('/plugin/weather/yr_pull/mysql/schema.yml');
    $this->builder->set_table_name('weather_yr_varsel');
    /**
     * 
     */
    wfPlugin::includeonce('wf/mysql');
    $this->mysql = new PluginWfMysql();
    $this->mysql->open($this->settings->get('mysql'));
  }
  private function load_data($url){
    $contents = simplexml_load_file($url);
    $contents = json_encode($contents);
    $contents = json_decode($contents,TRUE);
    $contents = new PluginWfArray($contents);
    $this->contents = $contents;
    $this->url = $url;
  }
  private function getContents($first = false){
    $contents_clean = array();
    foreach ($this->contents->get('forecast/tabular/time') as $key => $value) {
      $item = new PluginWfArray($value);
      $new = new PluginWfArray();
      $new->set('id',            wfCrypt::getUid());
      $new->set('url',           $this->url);
      $new->set('location_name', $this->contents->get('location/name'));
      $new->set('sun_rise',      $this->contents->get('sun/@attributes/rise'));
      $new->set('sun_set',       $this->contents->get('sun/@attributes/set'));
      $new->set('date_time', str_replace('T', ' ', $item->get('@attributes/from')));
      $new->set('windDirection_deg',   $item->get('windDirection/@attributes/deg'));
      $new->set('windDirection_code',  $item->get('windDirection/@attributes/code'));
      $new->set('windSpeed_mps',       $item->get('windSpeed/@attributes/mps'));
      $new->set('windSpeed_name',      $item->get('windSpeed/@attributes/name'));
      $new->set('temperature_unit',    $item->get('temperature/@attributes/unit'));
      $new->set('temperature_value',   $item->get('temperature/@attributes/value'));
      $new->set('pressure_unit',       $item->get('pressure/@attributes/unit'));
      $new->set('pressure_value',      $item->get('pressure/@attributes/value'));
      $new->set('precipitation_value',         $item->get('precipitation/@attributes/value'));
      $new->set('precipitation_minvalue',      $item->get('precipitation/@attributes/minvalue'));
      $new->set('precipitation_maxvalue',      $item->get('precipitation/@attributes/maxvalue'));
      $contents_clean[] = $new->get();
    }
    if($first){
      return new PluginWfArray($contents_clean[0]);
    }else{
      return $contents_clean;
    }
  }
  public function page_pull(){
    /**
     * 
     */
    $result = array();
    foreach ($this->settings->get('url') as $url) {
      $i = new PluginWfArray();
      /**
       * 
       */
      $this->load_data($url);
      $contents = $this->getContents(true);
      $i->set('contents', $contents->get());
      /**
       * 
       */
      $criteria = new PluginWfYml(__DIR__.'/data/criteria.yml', 'weather_yr_varsel');
      $criteria->set('where/weather_yr_varsel.location_name/value', $contents->get('location_name'));
      $criteria->set('where/weather_yr_varsel.date_time/value', $contents->get('date_time'));
      $sql_select = $this->builder->get_sql_select($criteria->get());
      $this->mysql->execute($sql_select);
      $rs = $this->mysql->getOne();
      /**
       * 
       */
      if(!$rs->get()){
        /**
         * Insert
         */
        $sql_insert = $this->builder->get_sql_insert($contents->get());
        $this->mysql->execute($sql_insert);
        $i->set('action', 'insert');
      }else{
        /**
         * Update
         */
        $contents->set('id', $rs->get('id'));
        $contents->set('updated_at', date('Y-m-d H:i:s'));
        $sql_update = $this->builder->get_sql_update($contents->get());
        $this->mysql->execute($sql_update);
        $i->set('action', 'update');
      }
      $result[] = $i->get();
    }
    wfHelp::yml_dump($result);
  }
}
