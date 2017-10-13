<?php
/**
<p>Product.</p>
<p>Check mysql folder for schema.yml.</p>
 */
class PluginWshopProduct_v1{
  public $settings = null;
  public $mysql = null;
  public $sql = null;
  function __construct($buto=false) {
    if($buto){
      wfPlugin::includeonce('wf/array');
      $settings = wfPlugin::getPluginSettings('wshop/product_v1', true);
      $this->settings = new PluginWfArray($settings->get('settings'));
      wfPlugin::includeonce('wf/mysql');
      $this->sql = wfSettings::getSettingsAsObject('/plugin/wshop/product_v1/mysql/sql.yml');
   }
  }
  /**
   * 
   */
  public function event_load_data(){
    /**
     * Product type.
     * Used in widget_product_list.
     */
    if(wfArray::get($GLOBALS, 'sys/method')=='products' && wfRequest::get('type')){
      $product_type = $this->getProductType(wfArray::get($GLOBALS, 'sys/settings/i18n/language'), wfRequest::get('type'));
      if($product_type){
        $GLOBALS = wfArray::set($GLOBALS, 'sys/wshop/product_type', $product_type);
        $GLOBALS = wfArray::set($GLOBALS, 'sys/page/settings/title', $product_type->get('name'));
        $GLOBALS = wfArray::set($GLOBALS, 'sys/page/settings/description', $product_type->get('description'));
      }
    }
    /**
     * Product.
     * Used in ....
     */
    if(wfArray::get($GLOBALS, 'sys/method')=='product' && wfRequest::get('id')){
      $product = $this->getProduct(wfArray::get($GLOBALS, 'sys/settings/i18n/language'), wfRequest::get('id'));
      if($product){
        $GLOBALS = wfArray::set($GLOBALS, 'sys/wshop/product', $product);
        $GLOBALS = wfArray::set($GLOBALS, 'sys/page/settings/description', $product->get('description'));
        $product_type = $this->getProductType(wfArray::get($GLOBALS, 'sys/settings/i18n/language'), $product->get('product_type_id'));
        if($product_type){
          $GLOBALS = wfArray::set($GLOBALS, 'sys/wshop/product_type', $product_type);
        }
        if($product_type){
          $GLOBALS = wfArray::set($GLOBALS, 'sys/page/settings/title', $product->get('name').' - '.$product_type->get('name'));
        }else{
          $GLOBALS = wfArray::set($GLOBALS, 'sys/page/settings/title', $product->get('name'));          
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
        //wfDocument::renderElement( array(wfDocument::createHtmlElement('div', array($obj->$method($this)), array('class' => 'table-responsive'))));
        wfDocument::renderElement(array($obj->$method($this)));
      }else{
        /**
         * Standard.
         */
        $this->sql->set('product_list/params/language/value', wfArray::get($GLOBALS, 'sys/settings/i18n/language'));
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
    $img_path_public = wfArray::get($GLOBALS, 'sys/app_dir').$this->settings->get('img_sys_dir').'/product';
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
   * Widget to show list-group of product types.
   */
  public function widget_product_type_list($data){
    /**
     * Cache file name.
     */
    $language = wfArray::get($GLOBALS, 'sys/settings/i18n/language');
    $cache_dir_file = wfArray::get($GLOBALS, 'sys/app_dir').$this->settings->get('img_sys_dir').'/cache/widget_product_type_list_'.$language.'.yml';
    /**
     * Delete file if not from today.
     */
    $this->deleteFileIfNotFromToday($cache_dir_file);
    /**
     * Get from db or cache file.
     */
    wfPlugin::includeonce('wf/yml');
    $yml = new PluginWfYml($cache_dir_file);
    if(!$yml->file_exists){
      /**
       * Get data.
       */
      $this->mysql = new PluginWfMysql();
      $this->mysql->open($this->settings->get('mysql'));
      $this->sql->set('product_type_list/params/language/value', wfArray::get($GLOBALS, 'sys/settings/i18n/language'));
      $this->mysql->execute($this->sql->get('product_type_list'));
      $rs = $this->mysql->getStmtAsArray();
      /**
       * Save to file.
       */
      $yml->set(null, $rs);
      $yml->save();
    }else{
      /**
       * Get data from file.
       */
      $rs = $yml->get();
    }
    /**
     * Create elements.
     */
    $list_group = wfDocument::createHtmlElementAsObject('div', null, array('class' => 'list-group'));
    $a = wfDocument::createHtmlElementAsObject('a', array(
      'h4' => wfDocument::createHtmlElement('h4', 'Headline', array('class' => 'list-group-item-heading')),
      'p' => wfDocument::createHtmlElement('p', 'Some text to show out.', array('class' => 'list-group-item-text'))
      ), array('class' => 'list-group-item'));
    /**
     * Set elements.
     */
    foreach ($rs as $key => $value) {
      $a->set('innerHTML/h4/innerHTML', $value['name']);
      $a->set('innerHTML/p/innerHTML', $value['description']);
      $a->set('attribute/href', '/p/products/type/'.$value['product_type_id'].'/'. $this->text_to_link($value['name']));
      $list_group->set('innerHTML/', $a->get());
    }
    /**
     * Render.
     */
    wfDocument::renderElement(array($list_group->get()));
  }
  /**
   * PRODUCT.
   */
  public function widget_product(){
    /**
     * Product.
     */
    if(wfArray::get($GLOBALS, 'sys/wshop/product')){
      $product = wfArray::get($GLOBALS, 'sys/wshop/product');
      wfDocument::renderElement(array(wfDocument::createHtmlElement('h1', $product->get('name'))));
      wfDocument::renderElement(array(wfDocument::createHtmlElement('h3', $product->get('description'))));
      wfDocument::renderElement(array(wfDocument::createHtmlElement('p', $product->get('description_more'))));
      $img_path = $this->settings->get('img_web_dir').'/product';
      $img_path_public = wfArray::get($GLOBALS, 'sys/app_dir').$this->settings->get('img_sys_dir').'/product';
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
      if(sizeof($images)>0){
        /**
         * Render primary image.
         */
        wfDocument::renderElement(array(wfDocument::createHtmlElement('img', null, array('id' => 'image_big', 'class' => 'img-rounded img-responsive', 'src' => $img_path.'/'.$images[0]))));
      }
      if(sizeof($images)>1){
        /**
         * Render more images.
         */
        $col = array();
        foreach ($images as $key => $value) {
          $img = wfDocument::createHtmlElement('img', null, array('id' => 'image_'.$key, 'class' => 'img-thumbnail', 'src' => $img_path.'/'.$value));
          $a = wfDocument::createHtmlElement('a', array($img), array('onclick' => "document.getElementById('image_big').src=document.getElementById('image_$key').src; return false;"));
          $col[] = wfDocument::createHtmlElement('div', array($a), array('class' => 'col-md-2'));
        }
        $row = wfDocument::createHtmlElement('div', $col, array('class' => 'row'));
        wfDocument::renderElement(array($row));
      }
    }
    /**
     * Product type.
     */
    if(wfArray::get($GLOBALS, 'sys/wshop/product_type')){
      $product_type = wfArray::get($GLOBALS, 'sys/wshop/product_type');
      wfDocument::renderElement(array(wfDocument::createHtmlElement('h3', $product_type->get('name'))));
      wfDocument::renderElement(array(wfDocument::createHtmlElement('p', array(wfDocument::createHtmlElement('em', $product_type->get('description'))))));
      wfDocument::renderElement(array(wfDocument::createHtmlElement('p', array(wfDocument::createHtmlElement('em', $product_type->get('description_more'))))));
    }
  }
  public function text_to_link($text){
    return str_replace(' ', '_', $text);
  }
  private function getElement($name){
    wfPlugin::includeonce('wf/yml');
    return new PluginWfYml('/plugin/wshop/product_v1/element/'.$name.'.yml');
  }
  /**
   * PRODUCT flash random.
   */
  public function widget_product_flash_random(){
    $language = wfArray::get($GLOBALS, 'sys/settings/i18n/language');
    $cache_dir_file = wfArray::get($GLOBALS, 'sys/app_dir').$this->settings->get('img_sys_dir').'/cache/widget_product_flash_random_'.$language.'.yml';
    /**
     * Delete file if not from today.
     */
    $this->deleteFileIfNotFromToday($cache_dir_file);
    /**
     * Get from db or cache file.
     */
    wfPlugin::includeonce('wf/yml');
    $yml = new PluginWfYml($cache_dir_file);
    if(!$yml->file_exists){
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
      if(strlen($ids)==0) return null;
      $ids = substr($ids, 0, strlen($ids)-1);
      /**
       * Get products.
       */
      $rs =  $this->mysql->runSql("select wshop_product.id, wshop_product_i18n.* from wshop_product inner join wshop_product_i18n on wshop_product.id=wshop_product_i18n.product_id where wshop_product.id in ($ids) and language='$language';");
      /**
       * Save to file.
       */
      $yml->set(null, $rs);
      $yml->save();
    }else{
      /**
       * Get data from file.
       */
      $rs = $yml->get();
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
      $element->setById('p', 'innerHTML', str_replace("\n", '<br>', $value['description']));
      $element->setById('a', 'attribute/href', '/'.$class.'/product/id/'.$value['id'].'/'. $this->text_to_link($value['name']));
      $list_group->set('innerHTML/', $element->get('list_group_item'));
    }
    return $list_group->get();
  }
}
