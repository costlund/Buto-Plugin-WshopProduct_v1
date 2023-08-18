<?php
/**
<p>Product.</p>
<p>Check mysql folder for schema.yml.</p>
 */
class PluginWshopProduct_v1{
  public $settings = null;
  public $mysql = null;
  public $sql = null;
  private $bootstrap = null;
  function __construct($buto=false) {
    if($buto){
      wfPlugin::includeonce('wf/array');
      $settings = wfPlugin::getPluginSettings('wshop/product_v1', true);
      $this->settings = new PluginWfArray($settings->get('settings'));
      $this->settings->set('img_web_dir', wfSettings::replaceDir( $this->settings->get('img_web_dir')));
      $this->settings->set('img_sys_dir', wfSettings::replaceDir( $this->settings->get('img_sys_dir')));
      wfPlugin::includeonce('wf/mysql');
      $this->sql = wfSettings::getSettingsAsObject('/plugin/wshop/product_v1/mysql/sql.yml');
      wfPlugin::enable('wf/table');
   }
    /**
     * Detect Bootstrap version.
     */
    $this->bootstrap = '3';
    $user = wfUser::getSession();
    if($user->get('plugin/twitter/bootstrap413v/include')){
      $this->bootstrap = '4';
    }
  }
  /**
   * 
   */
  public function event_load_data(){
    $language = wfI18n::getLanguage();
    /**
     * Product type.
     * Used in widget_product_list.
     */
    if(wfArray::get($GLOBALS, 'sys/method')=='products' && wfRequest::get('type')){
      $product_type = $this->getProductType($language, wfRequest::get('type'));
      if($product_type){
        wfGlobals::setSys('wshop/product_type', $product_type);
        wfGlobals::setSys('page/settings/title', $product_type->get('name'));
        wfGlobals::setSys('page/settings/description', $product_type->get('description'));
      }
    }
    /**
     * Product.
     * Used in ....
     */
    if(wfArray::get($GLOBALS, 'sys/method')=='product' && wfRequest::get('id')){
      $product = $this->getProduct($language, wfRequest::get('id'));
      if($product){
        wfGlobals::setSys('wshop/product', $product);
        wfGlobals::setSys('page/settings/description', $product->get('description'));
        $product_type = $this->getProductType($language, $product->get('product_type_id'));
        if($product_type){
          wfGlobals::setSys('wshop/product_type', $product_type);
        }
        if($product_type){
          wfGlobals::setSys('page/settings/title', $product->get('name').' - '.$product_type->get('name'));
        }else{
          wfGlobals::setSys('page/settings/title', $product->get('name'));
        }
      }
    }
  }
  private function getProductType($language, $id){
    $this->sql->set('product_type/params/language/value', $language);
    $this->sql->set('product_type/params/product_type_id/value', $id);
    $this->mysql = new PluginWfMysql();
    $this->mysql->open($this->settings->get('mysql'));
    $this->mysql->execute($this->sql->get('product_type'));
    $rs = $this->mysql->getStmtAsArray();
    if(sizeof($rs)>0){
      return new PluginWfArray($rs[0]);
    }else{
      return null;
    }
  }
  private function getProduct($language, $id){
    $this->mysql = new PluginWfMysql();
    $this->mysql->open($this->settings->get('mysql'));
    $this->sql->set('product/params/language/value', $language);
    $this->sql->set('product/params/id/value', $id);
    $this->mysql->execute($this->sql->get('product'));
    $rs = $this->mysql->getStmtAsArray();
    if(sizeof($rs)>0){
      return new PluginWfArray($rs[0]);
    }else{
      return null;
    }
  }
  /**
   * PRODUCTS.
   * Product type and list of products.
   * @param type $data
   */
  public function widget_product_list($data){
    $language = wfI18n::getLanguage();
    if(wfArray::get($GLOBALS, 'sys/wshop/product_type')){
      $data = new PluginWfArray($data);
      /**
       * Product Type.
       */
      $product_type = wfArray::get($GLOBALS, 'sys/wshop/product_type');
      $h1 = wfDocument::createHtmlElement('h1', $product_type->get('name'));
      wfDocument::renderElement(array($h1));
      $h2 = wfDocument::createHtmlElement('h3', $product_type->get('description'));
      wfDocument::renderElement(array($h2));
      $h2 = wfDocument::createHtmlElement('p', $product_type->get('description_more'));
      wfDocument::renderElement(array($h2));
      /**
       * Products.
       */
      if($data->get("data/type/".wfRequest::get('type')."/method")){
        /**
         * Custom method.
         */
        wfPlugin::includeonce($data->get("data/type/".wfRequest::get('type')."/method/plugin"));
        $obj = wfSettings::getPluginObj($data->get("data/type/".wfRequest::get('type')."/method/plugin"));
        $method = $data->get("data/type/".wfRequest::get('type')."/method/method");
        wfDocument::renderElement(array($obj->$method($this)));
      }else{
        /**
         * Standard.
         */
        $this->sql->set('product_list/params/language/value', $language);
        $this->sql->set('product_list/params/product_type_id/value', wfRequest::get('type'));
        $this->mysql = new PluginWfMysql();
        $this->mysql->open($this->settings->get('mysql'));
        $this->mysql->execute($this->sql->get('product_list'));
        $rs = $this->mysql->getStmtAsArray();
        /**
         * Render.
         */
        wfDocument::renderElement(array($this->getElementProductList($rs)));
      }
    }
  }
  public function imageExist($id){
    $img_path_public = wfArray::get($GLOBALS, 'sys/web_dir').$this->settings->get('img_web_dir').'/product';
    if(wfFilesystem::fileExist( $img_path_public.'/'.$id.'.jpg')){
      return true;
    }else{
      return false;
    }
  }
  public function getImageElement($id){
    $img_path = $this->settings->get('img_web_dir').'/product';
    $img = wfDocument::createHtmlElementAsObject('img', null, array('class' => 'img-thumbnail', 'style' => 'width:120px', 'src' => $img_path.'/'.$id.'.jpg'));
    return $img->get();
  }
  /**
   * List product types in a navbar. Can be placed direct in navbar or in a dropdown menu.
   */
  public function widget_products_navbar($data){
    /**
     * Get rs.
     */
    $rs = $this->getRsProductTypeList();
    /**
     * Create element and render.
     */
    $element = array();
    foreach ($rs as $key => $value) {
      $element[] = wfDocument::createHtmlElement('li', array(wfDocument::createHtmlElement('a', $value['name'], array('href' => '/p/products/type/'.$value['product_type_id'].'/'. $this->text_to_link($value['name'])))));
    }
    wfDocument::renderElement($element);
  }
  /**
   * Bootstrap Carousel with product categorys.
   */
  public function widget_carousel($data){
    /**
     * Image path.
     */
    $img_path = $this->settings->get('img_web_dir').'/type';
    /**
     * 
     */
    wfPlugin::includeonce('wf/yml');
    /**
     * Get rs and create elements.
     */
    $rs = $this->getRsProductTypeList();
    /**
     * 
     */
    if($this->bootstrap=='3'){
      $first = true;
      $caroulse = new PluginWfYml("/plugin/wshop/product_v1/element/carousel.yml");
      $carousel_wshop_indecators = array();
      $carousel_wshop_inner = array();
      foreach ($rs as $key => $value) {
        $active = '';
        if($first){
          $first = false;
          $active = 'active';
        }
        $carousel_wshop_indecators[] = wfDocument::createHtmlElement('li', null, array('class' => "$active", 'data-target' => '#carousel-wshop', 'data-slide-to' => $key));
        $carousel_wshop_inner[] = wfDocument::createHtmlElement('div', array(
          wfDocument::createHtmlElement('a', array(
            wfDocument::createHtmlElement('img', null, array('src' => $img_path.'/'.$value['product_type_id'].'.jpg')),
            wfDocument::createHtmlElement('div', array(
              wfDocument::createHtmlElement('h1', $value['name']),
              wfDocument::createHtmlElement('p1', $value['description'])
            ), array('class' => 'carousel-caption'))
          ), array('href' => '/p/products/type/'.$value['product_type_id'].'/'. $this->text_to_link($value['name'])))
        ), array('class' => "item $active"));
      }
      $caroulse->setById('carousel-wshop-indicators', 'innerHTML', $carousel_wshop_indecators);
      $caroulse->setById('carousel-wshop-inner', 'innerHTML', $carousel_wshop_inner);
      wfDocument::renderElement(array($caroulse->get()));
    }
    /**
     * 
     */
    if($this->bootstrap=='4'){
      $carousel_wshop_inner = array();
      foreach ($rs as $key => $value) {
        $img_src = $img_path.'/'.$value['product_type_id'].'.jpg';
        $href = '/p/products/type/'.$value['product_type_id'].'/'. $this->text_to_link($value['name']);
        $carousel_wshop_inner[] = wfDocument::createHtmlElement('div', array(
          wfDocument::createHtmlElement('a', array(
            wfDocument::createHtmlElement('h1', $value['name']),
            wfDocument::createHtmlElement('p1', $value['description'])
          ), array('class' => 'carousel-caption', 'href' => $href))
        ), array('style' => "background:gray;min-height:300px;background-image: url('$img_src');background-repeat:no-repeat;background-position:center;"));
      }
      wfPlugin::enable('bootstrap/carousel_v1');
      $widget = wfDocument::createWidget('bootstrap/carousel_v1', 'carousel', array('id' => 'my_wshop_carousel', 'item' => $carousel_wshop_inner));
      wfDocument::renderElement(array($widget));
    }
  }
  /**
   * 
   */
  private function getRsProductTypeList(){
    /**
     * 
     */
    $rs = null;
    /**
     * New code...
     */
    $language = wfI18n::getLanguage();
    if(wfFilesystem::isCache()){
      $cache_file = 'plugin_wshop_product_v1_product_type_list_'.$language.'.yml.cache';
      if(wfFilesystem::fileExist(wfFilesystem::getCacheFolder().'/'.$cache_file)){
        /**
         * Cache exist.
         * Get it.
         */
        $rs = wfFilesystem::getCacheFile($cache_file);
      }else{
        /**
         * Get data.
         */
        $this->mysql = new PluginWfMysql();
        $this->mysql->open($this->settings->get('mysql'));
        $this->sql->set('product_type_list/params/language/value', $language);
        $this->mysql->execute($this->sql->get('product_type_list'));
        $rs = $this->mysql->getStmtAsArray();
        wfFilesystem::saveFile(wfFilesystem::getCacheFolder().'/'.$cache_file, serialize($rs));
      }
    }else{
      /**
       * Get data.
       */
      $this->mysql = new PluginWfMysql();
      $this->mysql->open($this->settings->get('mysql'));
      $this->sql->set('product_type_list/params/language/value', $language);
      $this->mysql->execute($this->sql->get('product_type_list'));
      $rs = $this->mysql->getStmtAsArray();
    }
    return $rs;
  }
  /**
   * Widget to show list-group of product types.
   */
  public function widget_product_type_list($data){
    $rs = $this->getRsProductTypeList();
    /**
     * Create elements.
     */
    $list_group = wfDocument::createHtmlElementAsObject('div', null, array('class' => 'list-group'));
    $a = wfDocument::createHtmlElementAsObject('a', array(
      'h4' => wfDocument::createHtmlElement('h4', 'Headline', array('class' => 'list-group-item-heading')),
      'p' => wfDocument::createHtmlElement('p', 'Some text to show out.', array('class' => 'list-group-item-text'))
      ), array('class' => 'list-group-item'));
    /**
     * Product.
     */
    if(wfArray::get($GLOBALS, 'sys/wshop/product')){
      $product = wfArray::get($GLOBALS, 'sys/wshop/product');
    }else{
      $product = new PluginWfArray();
    }
    /**
     * Set elements.
     */
    foreach ($rs as $key => $value) {
      $a->set('innerHTML/h4/innerHTML', $value['name']);
      $a->set('innerHTML/p/innerHTML', $value['description']);
      $a->set('attribute/href', '/p/products/type/'.$value['product_type_id'].'/'. $this->text_to_link($value['name']));
      if(wfRequest::get('type')==$value['product_type_id'] || $product->get('product_type_id') == $value['product_type_id']){
        $a->set('attribute/class', 'list-group-item active');
      }else{
        $a->set('attribute/class', 'list-group-item');
      }
      
      $list_group->set('innerHTML/', $a->get());
    }
    /**
     * Render.
     */
    wfDocument::renderElement(array($list_group->get()));
  }
  private function getImages($product){
    $images = array();
    /**
     * Images.
     */
    if($this->imageExist($product->get('id'))){
      /**
       * Primary image.
       */
      $images[] = $product->get('id').'.jpg';
    }
    for($i=1;$i<10;$i++){
      /**
       * More images.
       */
      if($this->imageExist($product->get('id').'_'.$i)){
        $images[] = $product->get('id').'_'.$i.'.jpg';
      }
    }
    return $images;
  }
  /**
   * PRODUCT.
   */
  public function widget_product(){
    $element = $this->getElement('product');
    if(wfArray::get($GLOBALS, 'sys/wshop/product')){
      $product = wfArray::get($GLOBALS, 'sys/wshop/product');
      /**
       * Specification
       */
      if($product->get('specification')){
        wfPlugin::includeonce('string/array');
        $sa = new PluginStringArray();
        $array = $sa->from_br($product->get('specification'));
        $rows = new PluginWfArray();
        foreach ($array as $v) {
          $temp = new PluginWfArray($sa->from_char($v, ':'));
          $rows->set($temp->get('0'), $temp->get('1'));
        }
        $product->set('specification_rows', $rows->get());
      }else{
      }
      /**
       * 
       */
      if($this->bootstrap=='3'){
        $product->set('image_big_class', 'img-rounded img-responsive');
      }elseif($this->bootstrap=='4'){
        $product->set('image_big_class', 'img-fluid');
      }
      $img_path = $this->settings->get('img_web_dir').'/product';
      $images = $this->getImages($product);
      if(sizeof($images)>0){
        /**
         * Render primary image.
         */
        $product->set('image_big', $img_path.'/'.$images[0]);
      }
      $thumbnail = array();
      if(sizeof($images)>1){
        /**
         * Render more images.
         */
        foreach ($images as $key => $value) {
          $img = wfDocument::createHtmlElement('img', null, array('id' => 'image_'.$key, 'class' => 'img-thumbnail', 'src' => $img_path.'/'.$value));
          $a = wfDocument::createHtmlElement('a', array($img), array('onclick' => "document.getElementById('image_big').src=document.getElementById('image_$key').src; return false;"));
          $thumbnail[] = wfDocument::createHtmlElement('div', array($a), array('class' => 'col-md-2'));
        }
      }
      $product->set('image_thumbnails', $thumbnail);
      $element->setByTag($product->get());
      if(wfArray::get($GLOBALS, 'sys/wshop/product_type')){
        $product_type = wfArray::get($GLOBALS, 'sys/wshop/product_type');
        $element->setByTag($product_type->get(), 'type');
      }
    }
    //wfHelp::yml_dump($product);
    wfDocument::renderElement($element->get());
  }
  public function text_to_link($text){
    $text = wfPhpfunc::str_replace(' ', '_', $text);
    $text = wfPhpfunc::str_replace('.', '_', $text);
    return $text;
  }
  private function getElement($name){
    wfPlugin::includeonce('wf/yml');
    return new PluginWfYml('/plugin/wshop/product_v1/element/'.$name.'.yml');
  }
  private function getMysqlRandomProductTypes(){
    $language = wfI18n::getLanguage();
    /**
     * Get data from db.
     * First get one id for each category random.
     */
    $this->mysql = new PluginWfMysql();
    $this->mysql->open($this->settings->get('mysql'));
    $rs =  $this->mysql->runSql("select id, ( select id from wshop_product where product_type_id=wshop_product_type.id order by rand() limit 1) as random_product_id from wshop_product_type;");
    $ids = '';
    foreach ($rs['data'] as $key => $value) {
      $ids .= "'".$value['random_product_id']."',";
    }
    if(wfPhpfunc::strlen($ids)==0) return null;
    $ids = wfPhpfunc::substr($ids, 0, wfPhpfunc::strlen($ids)-1);
    /**
     * Get products.
     */
    $rs =  $this->mysql->runSql("select wshop_product.id, wshop_product_i18n.* from wshop_product inner join wshop_product_i18n on wshop_product.id=wshop_product_i18n.product_id where wshop_product.id in ($ids) and language='$language';");
    return $rs;
  }
  /**
   * PRODUCT flash random.
   */
  public function widget_product_flash_random(){
    $language = wfI18n::getLanguage();
    /**
     * New code...
     */
    if(wfFilesystem::isCache()){
      $cache_file = 'plugin_wshop_product_v1_product_flash_random_'.$language.'.yml.cache';
      /**
       * Delete file if exist and not from today.
       */
      $this->deleteFileIfNotFromToday(wfFilesystem::getCacheFolder().'/'.$cache_file);
      if(wfFilesystem::fileExist(wfFilesystem::getCacheFolder().'/'.$cache_file)){
        /**
         * Cache exist.
         * Get it.
         */
        $rs = wfFilesystem::getCacheFile($cache_file);
      }else{
        /**
         * Get data.
         */
        $rs = $this->getMysqlRandomProductTypes();
        wfFilesystem::saveFile(wfFilesystem::getCacheFolder().'/'.$cache_file, serialize($rs));
      }
    }else{
      /**
       * Get data.
       */
      $rs = $this->getMysqlRandomProductTypes();
    }
    /**
     * Render.
     */
    wfDocument::renderElement(array($this->getElementProductList($rs['data'])));
  }
  private function deleteFileIfNotFromToday($cache_dir_file){
    if(wfFilesystem::fileExist($cache_dir_file)){
      $file_time = wfFilesystem::getFiletime($cache_dir_file);
      if(date('Y-m-d', $file_time) != date('Y-m-d')){
        wfFilesystem::delete($cache_dir_file);
      }
    }
  }
  /**
   * Get products in a list-group.
   * @param type $rs
   * @return type
   */
  private function getElementProductList($rs){
    $class = null;
    if($this->settings->get('class')){
      $class = $this->settings->get('class');
    }else{
      $class = wfArray::get($GLOBALS, 'sys/class');
    }
    $element = $this->getElement('product_list_item');
    $list_group = wfDocument::createHtmlElementAsObject('div', null, array('class' => 'list-group'));
    foreach ($rs as $key => $value) {
      if($this->imageExist($value['id'])){
        $element->setById('right_column', 'innerHTML', array(
          $this->getImageElement($value['id'])
                ));
      }else{
        $element->setById('right_column', 'innerHTML', null);
      }
      $element->setById('h4', 'innerHTML', $value['name']);
      $element->setById('p', 'innerHTML', wfPhpfunc::str_replace("\n", '<br>', $value['description']));
      $element->setById('a', 'attribute/href', '/'.$class.'/product/id/'.$value['id'].'/'. $this->text_to_link($value['name']));
      $list_group->set('innerHTML/', $element->get('list_group_item'));
    }
    return $list_group->get();
  }
}
