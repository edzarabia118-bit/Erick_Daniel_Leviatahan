<?php
require_once __DIR__ . '/conexion/conexion.php';
$names = ['Te de jamaica','Agua','Vasos 8oz'];
foreach($names as $n){
 $st=$conn->prepare('SELECT stock, unidad FROM inventario WHERE nombre=?');
 $st->bind_param('s',$n);
 $st->execute();
 $r=$st->get_result()->fetch_assoc();
 echo $n.'|'.($r['stock'] ?? 'NA').'|'.($r['unidad'] ?? 'NA').PHP_EOL;
}
