## SEQUENCIA DE COMANDOS

php artisan import:ploutos-plans-and-persist
php artisan import:pending-product-from-mercado-livre

php artisan queue:work --tries=10 --timeout=36001

php artisan create:modified-content-copy-right

php artisan export:mapped-product-to-self-ecommerce-tool {--forced_list_skus=?}

php artisan import:plan-import-ean-and-shipping-data-to-self-commerce {--send_my_app=no} {--just_send_my_app=no}


## queue work

php artisan queue:work --timeout=8600  --memory=4024

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_mercado_livre_scrap 

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_modify_copyright 

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_send_self_ecommerce 

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_send_self_ecommerce_maintance 

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_send_self_ecommerce_shipping_data_maintance 

### tambem posivel

php artisan queue:work --timeout=8600  --memory=4024 --queue=queue_send_self_ecommerce,default 
