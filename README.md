# Buto-Plugin-WshopProduct_v1


Mysql and web dir settings.
```
plugin:
  wshop:
    product_v1:
      enabled: true
      settings:
        mysql: 'yml:/../buto_data/theme/[theme]/data.yml:mysql'
        img_web_dir: /data/theme/xxx/yyy/wshop
```

An event must be set to load data into Globals param.
```
events:
  module_method_after:
    -
      plugin: wshop/product_v1
      method: load_data
```



Type links to Products.
```
type: ul
innerHTML:
  -
    type: widget
    data:
      plugin: wshop/product_v1
      method: products_navbar
```

Type links to Products.
```
type: widget
data:
  plugin: wshop/product_v1
  method: product_type_list
  data: null
```

Carousel with type images.
```
type: widget
data:
  plugin: wshop/product_v1
  method: carousel
```



Products has to be on page /xxx/products.
```
type: widget
data:
  plugin: wshop/product_v1
  method: product_list
```
Product has to be on page /xxx/product.
```
type: widget
data:
  plugin: wshop/product_v1
  method: product
```


